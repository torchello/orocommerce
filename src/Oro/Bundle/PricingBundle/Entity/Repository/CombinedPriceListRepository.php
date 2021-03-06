<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToAccount;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToAccountGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class CombinedPriceListRepository extends BasePriceListRepository
{
    const CPL_BATCH_SIZE = 100;

    /**
     * @param CombinedPriceList $combinedPriceList
     * @return CombinedPriceListToPriceList[]
     */
    public function getPriceListRelations(CombinedPriceList $combinedPriceList)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('partial cpl.{id, priceList, mergeAllowed}')
            ->from('OroPricingBundle:CombinedPriceListToPriceList', 'cpl')
            ->where($qb->expr()->eq('cpl.combinedPriceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList)
            ->orderBy('cpl.sortOrder');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Account $account
     * @param Website $website
     * @param bool|true $isEnabled
     * @return null|CombinedPriceList
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPriceListByAccount(Account $account, Website $website, $isEnabled = true)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                'OroPricingBundle:CombinedPriceListToAccount',
                'priceListToAccount',
                Join::WITH,
                'priceListToAccount.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToAccount.account', ':account'))
            ->andWhere($qb->expr()->eq('priceListToAccount.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.enabled', ':enabled'))
            ->setParameter('account', $account)
            ->setParameter('website', $website)
            ->setParameter('enabled', $isEnabled)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }


    /**
     * @param AccountGroup $accountGroup
     * @param Website $website
     * @param bool|true $isEnabled
     * @return null|CombinedPriceList
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPriceListByAccountGroup(AccountGroup $accountGroup, Website $website, $isEnabled = true)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                'OroPricingBundle:CombinedPriceListToAccountGroup',
                'priceListToAccountGroup',
                Join::WITH,
                'priceListToAccountGroup.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToAccountGroup.accountGroup', ':accountGroup'))
            ->andWhere($qb->expr()->eq('priceListToAccountGroup.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.enabled', ':enabled'))
            ->setParameter('accountGroup', $accountGroup)
            ->setParameter('website', $website)
            ->setParameter('enabled', $isEnabled)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Website $website
     * @param bool|true $isEnabled
     * @return CombinedPriceList|null
     */
    public function getPriceListByWebsite(Website $website, $isEnabled = true)
    {
        $qb = $this->createQueryBuilder('priceList');

        $qb
            ->innerJoin(
                'OroPricingBundle:CombinedPriceListToWebsite',
                'priceListToWebsite',
                Join::WITH,
                'priceListToWebsite.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToWebsite.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.enabled', ':enabled'))
            ->setParameter('website', $website)
            ->setParameter('enabled', $isEnabled)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array CombinedPriceList[] $exceptPriceLists
     * @param bool|null $priceListsEnabled
     */
    public function deleteUnusedPriceLists(array $exceptPriceLists = [], $priceListsEnabled = true)
    {
        $iterator = $this->getUnusedPriceListsIterator($exceptPriceLists, $priceListsEnabled);
        $bufferSize = $this->getBufferSize();
        $iterator->setBufferSize($bufferSize);

        $deleteQb = $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'cplDelete');

        $deleteQb->where($deleteQb->expr()->in('cplDelete.id', ':unusedPriceLists'));

        $priceListsIdForDelete = [];
        $i = 0;
        foreach ($iterator as $priceList) {
            $priceListsIdForDelete[] = $priceList->getId();
            $i++;
            if ($i === $bufferSize) {
                $deleteQb->setParameter('unusedPriceLists', $priceListsIdForDelete)
                    ->getQuery()->execute();
                $priceListsIdForDelete = [];
                $i = 0;
            }
        }
        if ($priceListsIdForDelete) {
            $deleteQb->setParameter('unusedPriceLists', $priceListsIdForDelete)
                ->getQuery()->execute();
        }
    }

    /**
     * @return int
     */
    protected function getBufferSize()
    {
        return BufferedQueryResultIterator::DEFAULT_BUFFER_SIZE;
    }

    /**
     * @param array $exceptPriceLists
     * @param bool|null $priceListsEnabled
     * @return BufferedQueryResultIterator
     */
    protected function getUnusedPriceListsIterator(array $exceptPriceLists = [], $priceListsEnabled = true)
    {
        $selectQb = $this->createQueryBuilder('priceList')
            ->select('priceList');

        $relations = [
            'priceListToWebsite' => 'OroPricingBundle:CombinedPriceListToWebsite',
            'priceListToAccountGroup' => 'OroPricingBundle:CombinedPriceListToAccountGroup',
            'priceListToAccount' => 'OroPricingBundle:CombinedPriceListToAccount',
        ];

        foreach ($relations as $alias => $entityName) {
            $selectQb->leftJoin(
                $entityName,
                $alias,
                Join::WITH,
                $selectQb->expr()->eq($alias . '.priceList', 'priceList.id')
            );
            $selectQb->andWhere($alias . '.priceList IS NULL');
        }
        $selectQb->leftJoin(
            'OroPricingBundle:CombinedPriceListActivationRule',
            'rule',
            Join::WITH,
            $selectQb->expr()->eq('rule.combinedPriceList', 'priceList.id')
        );
        $selectQb->andWhere($selectQb->expr()->isNull('rule.combinedPriceList'));
        if ($exceptPriceLists) {
            $selectQb->andWhere($selectQb->expr()->notIn('priceList', ':exceptPriceLists'))
                ->setParameter('exceptPriceLists', $exceptPriceLists);
        }
        if ($priceListsEnabled !== null) {
            $selectQb->andWhere($selectQb->expr()->eq('priceList.enabled', ':isEnabled'))
                ->setParameter('isEnabled', $priceListsEnabled);
        }

        return new BufferedQueryResultIterator($selectQb->getQuery());
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param CombinedPriceList $activeCpl
     * @param Website $website
     * @param Account|AccountGroup $targetEntity
     */
    public function updateCombinedPriceListConnection(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $activeCpl,
        Website $website,
        $targetEntity = null
    ) {
        $em = $this->getEntityManager();
        $relation = null;
        if ($targetEntity instanceof Account) {
            $relation = $em->getRepository('OroPricingBundle:CombinedPriceListToAccount')
                ->findOneBy(['account' => $targetEntity, 'website' => $website]);
            if (!$relation) {
                $relation = new CombinedPriceListToAccount();
                $relation->setAccount($targetEntity);
                $relation->setWebsite($website);
                $relation->setPriceList($combinedPriceList);
                $relation->setFullChainPriceList($combinedPriceList);
                $em->persist($relation);
            }
        } elseif ($targetEntity instanceof AccountGroup) {
            $relation = $em->getRepository('OroPricingBundle:CombinedPriceListToAccountGroup')
                ->findOneBy(['accountGroup' => $targetEntity, 'website' => $website]);
            if (!$relation) {
                $relation = new CombinedPriceListToAccountGroup();
                $relation->setAccountGroup($targetEntity);
                $relation->setWebsite($website);
                $relation->setPriceList($combinedPriceList);
                $relation->setFullChainPriceList($combinedPriceList);
                $em->persist($relation);
            }
        } elseif (!$targetEntity) {
            $relation = $em->getRepository('OroPricingBundle:CombinedPriceListToWebsite')
                ->findOneBy(['website' => $website]);
            if (!$relation) {
                $relation = new CombinedPriceListToWebsite();
                $relation->setWebsite($website);
                $relation->setPriceList($combinedPriceList);
                $relation->setFullChainPriceList($combinedPriceList);
                $em->persist($relation);
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown target "%s"', get_class($targetEntity)));
        }
        $relation->setFullChainPriceList($combinedPriceList);
        $relation->setPriceList($activeCpl);
        $em->flush($relation);
    }

    /**
     * @param PriceList $priceList
     * @param null $hasCalculatedPrices
     * @return BufferedQueryResultIterator
     */
    public function getCombinedPriceListsByPriceList(PriceList $priceList, $hasCalculatedPrices = null)
    {
        $qb = $this->createQueryBuilder('cpl');

        $qb->select('DISTINCT cpl')
            ->innerJoin(
                'OroPricingBundle:CombinedPriceListToPriceList',
                'priceListRelations',
                Join::WITH,
                $qb->expr()->eq('cpl', 'priceListRelations.combinedPriceList')
            )
            ->where($qb->expr()->eq('priceListRelations.priceList', ':priceList'))
            ->setParameter('priceList', $priceList);
        if ($hasCalculatedPrices !== null) {
            $qb->andWhere($qb->expr()->eq('cpl.pricesCalculated', ':hasCalculatedPrices'))
                ->setParameter('hasCalculatedPrices', $hasCalculatedPrices);
        }

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param array|PriceList[]|int[] $priceLists
     * @return BufferedQueryResultIterator
     */
    public function getCombinedPriceListsByPriceLists(array $priceLists)
    {
        $qb = $this->createQueryBuilder('cpl');

        $qb->select('cpl')
            ->innerJoin(
                CombinedPriceListToPriceList::class,
                'priceListRelations',
                Join::WITH,
                $qb->expr()->eq('cpl', 'priceListRelations.combinedPriceList')
            )
            ->where($qb->expr()->in('priceListRelations.priceList', ':priceLists'))
            ->setParameter('priceLists', $priceLists);

        return new BufferedQueryResultIterator($qb->getQuery());
    }

    /**
     * @param int $offsetHours
     *
     * @return BufferedQueryResultIterator|CombinedPriceList[]
     */
    public function getCPLsForPriceCollectByTimeOffset($offsetHours)
    {
        $activateDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $activateDate->add(new \DateInterval(sprintf('PT%dM', $offsetHours * 60)));

        $qb = $this->createQueryBuilder('cpl');
        $qb->select('distinct cpl')
            ->join(
                'OroPricingBundle:CombinedPriceListActivationRule',
                'combinedPriceListActivationRule',
                Join::WITH,
                $qb->expr()->eq('cpl', 'combinedPriceListActivationRule.combinedPriceList')
            )
            ->where($qb->expr()->eq('cpl.pricesCalculated', ':pricesCalculated'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->lt('combinedPriceListActivationRule.activateAt', ':activateData'),
                    $qb->expr()->isNull('combinedPriceListActivationRule.activateAt')
                )
            )
            ->setParameters([
                'pricesCalculated' => false,
                'activateData' => $activateDate
            ]);

        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize(self::CPL_BATCH_SIZE);

        return $iterator;
    }
}
