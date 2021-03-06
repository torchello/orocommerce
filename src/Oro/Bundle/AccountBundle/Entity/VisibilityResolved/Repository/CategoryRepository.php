<?php

namespace Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - account
 *  - category
 */
class CategoryRepository extends EntityRepository
{
    use CategoryVisibilityResolvedTermTrait;
    use BasicOperationRepositoryTrait;

    const INSERT_BATCH_SIZE = 500;

    /**
     * @param Category $category
     * @param int $configValue
     * @return bool
     */
    public function isCategoryVisible(Category $category, $configValue)
    {
        $visibility = $this->getFallbackToAllVisibility($category);
        if ($visibility === CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
            $visibility = $configValue;
        }

        return $visibility === CategoryVisibilityResolved::VISIBILITY_VISIBLE;
    }

    /**
     * @param int $visibility visible|hidden
     * @param int $configValue
     * @return array
     */
    public function getCategoryIdsByVisibility($visibility, $configValue)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroCatalogBundle:Category', 'category')
            ->orderBy('category.id');

        $terms = [$this->getCategoryVisibilityResolvedTerm($qb, $configValue)];

        if ($visibility === CategoryVisibilityResolved::VISIBILITY_VISIBLE) {
            $qb->andWhere($qb->expr()->gt(implode(' + ', $terms), 0));
        } else {
            $qb->andWhere($qb->expr()->lte(implode(' + ', $terms), 0));
        }

        $categoryVisibilityResolved = $qb->getQuery()->getArrayResult();

        return array_map('current', $categoryVisibilityResolved);
    }

    /**
     * @param int $visibility visible|hidden|config
     * @return array
     */
    public function getCategoryIdsByNotResolvedVisibility($visibility)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('category.id')
            ->from('OroCatalogBundle:Category', 'category')
            ->leftJoin(
                'Oro\Bundle\AccountBundle\Entity\VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq($this->getRootAlias($qb), 'cvr.category')
            )
            ->orderBy('category.id');

        if ($visibility === CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG) {
            $condition = sprintf('cvr.visibility IS NULL OR cvr.visibility = %s', $visibility);
        } else {
            $condition = sprintf('cvr.visibility = %s', $visibility);
        }
        $qb->andWhere($condition);

        return array_map('current', $qb->getQuery()->getArrayResult());
    }

    public function clearTable()
    {
        // TRUNCATE can't be used because it can't be rolled back in case of DB error
        $this->createQueryBuilder('cvr')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     */
    public function insertStaticValues(InsertFromSelectQueryExecutor $insertExecutor)
    {
        $visibilityCondition = sprintf(
            "CASE WHEN cv.visibility = '%s' THEN %s ELSE %s END",
            CategoryVisibility::VISIBLE,
            CategoryVisibilityResolved::VISIBILITY_VISIBLE,
            CategoryVisibilityResolved::VISIBILITY_HIDDEN
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'cv.id',
                'IDENTITY(cv.category)',
                $visibilityCondition,
                (string)CategoryVisibilityResolved::SOURCE_STATIC
            )
            ->from('OroAccountBundle:Visibility\CategoryVisibility', 'cv')
            ->where('cv.visibility != :config')
            ->setParameter('config', CategoryVisibility::CONFIG);

        $insertExecutor->execute(
            $this->getClassName(),
            ['sourceCategoryVisibility', 'category', 'visibility', 'source'],
            $queryBuilder
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param array $categoryIds
     * @param int $visibility
     */
    public function insertParentCategoryValues(
        InsertFromSelectQueryExecutor $insertExecutor,
        array $categoryIds,
        $visibility
    ) {
        if (!$categoryIds) {
            return;
        }

        $sourceCondition = sprintf(
            'CASE WHEN c.parentCategory IS NOT NULL THEN %s ELSE %s END',
            CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            CategoryVisibilityResolved::SOURCE_STATIC
        );

        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select(
                'c.id',
                (string)$visibility,
                $sourceCondition
            )
            ->from('OroCatalogBundle:Category', 'c')
            ->leftJoin('OroAccountBundle:Visibility\CategoryVisibility', 'cv', 'WITH', 'cv.category = c')
            ->andWhere('cv.visibility IS NULL')     // parent category fallback
            ->andWhere('c.id IN (:categoryIds)');   // specific category IDs

        foreach (array_chunk($categoryIds, self::INSERT_BATCH_SIZE) as $ids) {
            $queryBuilder->setParameter('categoryIds', $ids);
            $insertExecutor->execute(
                $this->getClassName(),
                ['category', 'visibility', 'source'],
                $queryBuilder
            );
        }
    }

    /**
     * @param Category $category
     * @return int visible|hidden|config
     */
    public function getFallbackToAllVisibility(Category $category)
    {
        $configFallback = CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COALESCE(cvr.visibility, '. $qb->expr()->literal($configFallback).')')
            ->from('OroCatalogBundle:Category', 'category')
            ->leftJoin(
                'OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                Join::WITH,
                $qb->expr()->eq('cvr.category', 'category')
            )
            ->where($qb->expr()->eq('category', ':category'))
            ->setParameter('category', $category);

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array $categoryIds
     * @param int $visibility
     */
    public function updateCategoryVisibilityByCategory(array $categoryIds, $visibility)
    {
        if (!$categoryIds) {
            return;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->update('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved', 'cvr')
            ->set('cvr.visibility', $visibility)
            ->andWhere($qb->expr()->in('IDENTITY(cvr.category)', ':categoryIds'))
            ->setParameter('categoryIds', $categoryIds);

        $qb->getQuery()->execute();
    }
}
