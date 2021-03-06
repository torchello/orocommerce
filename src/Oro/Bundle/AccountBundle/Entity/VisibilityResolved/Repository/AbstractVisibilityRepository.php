<?php

namespace Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractVisibilityRepository extends EntityRepository
{
    /**
     * @param Website $website
     * @return int
     */
    public function clearTable(Website $website = null)
    {
        $qb = $this->createQueryBuilder('visibility_resolved')
            ->delete();

        if ($website) {
            $qb->andWhere('visibility_resolved.website = :website')
                ->setParameter('website', $website);
        }

        return $qb->getQuery()
            ->execute();
    }
}
