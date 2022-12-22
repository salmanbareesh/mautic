<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Connection;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class LeadCategoryRepository.
 */
class LeadCategoryRepository extends CommonRepository
{
    public function getLeadCategories(Lead $lead)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('lc.id, lc.category_id, lc.date_added, lc.manually_added, lc.manually_removed, c.alias, c.title')
            ->from(MAUTIC_TABLE_PREFIX.'lead_categories', 'lc')
            ->join('lc', MAUTIC_TABLE_PREFIX.'categories', 'c', 'c.id = lc.category_id')
            ->where('lc.lead_id = :lead')->setParameter('lead', $lead->getId());
        $results = $q->execute()
            ->fetchAll();

        $categories = [];
        foreach ($results as $category) {
            $categories[$category['category_id']] = $category;
        }

        return $categories;
    }

    /**
     * @param string[] $types
     *
     * @return array<int, int>
     */
    public function getSubscribedAndNewCategoryIds(Lead $lead, array $types): array
    {
        $criteria = Criteria::create()
            ->andWhere(Criteria::expr()->eq('manuallyRemoved', 1));

        return $this->getLeadCategoriesMapping($lead, $types, $criteria);
    }

    /**
     * @param string[] $types
     *
     * @return array<int, int>
     */
    public function getNonAssociatedCategoryIdsForAContact(Lead $lead, array $types): array
    {
        return $this->getLeadCategoriesMapping($lead, $types);
    }

    /**
     * @param string[] $types
     *
     * @return array<int, int>
     */
    private function getLeadCategoriesMapping(Lead $lead, array $types, Criteria $criteria = null): array
    {
        $parentQ = $this->getEntityManager()->getRepository(Category::class)->createQueryBuilder('c');
        $parentQ->select('c.id');
        $parentQ->where('c.isPublished = :isPublished');
        $parentQ->setParameter('isPublished', 1);
        $parentQ->andWhere($parentQ->expr()->in('c.bundle', ':bundles'));
        $parentQ->setParameter('bundles', $types, Connection::PARAM_STR_ARRAY);

        // Get the category ids for particular lead
        $subQ = $this->getEntityManager()->getRepository(LeadCategory::class)->createQueryBuilder('lc');
        $subQ->select('IDENTITY(lc.category)');
        $subQ->where($subQ->expr()->eq('lc.lead', ':leadId'));
        $subQ->setParameter('leadId', $lead->getId());

        if ($criteria) {
            $subQ->addCriteria($criteria);
        }

        // Add sub-query
        $parentQ->andWhere($parentQ->expr()->notIn('c.id', $subQ->getDQL()));

        // Add sub-query parameter.
        foreach ($subQ->getParameters() as $parameter) {
            $parentQ->setParameter($parameter->getName(), $parameter->getValue(), $parameter->getType());
        }

        $leadCategories = $parentQ->getQuery()->getResult();

        $leadCategoryList = [];
        foreach ($leadCategories as $category) {
            $id = (int) $category['id'];

            $leadCategoryList[$id] = $id;
        }

        return $leadCategoryList;
    }
}
