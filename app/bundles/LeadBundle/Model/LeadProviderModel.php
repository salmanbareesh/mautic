<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;

/**
 * Class LeadProviderModel.
 */
class LeadProviderModel
{
    /** @var EntityManager */
    private $em;

    /** @var LeadModel */
    private $leadModel;

    /**
     * LeadProviderModel constructor.
     *
     * @param EntityManager $entityManager
     * @param LeadModel     $leadModel
     */
    public function __construct(EntityManager $entityManager, LeadModel $leadModel)
    {
        $this->em        = $entityManager;
        $this->leadModel = $leadModel;
    }

    /**
     * @param Lead $template
     * @param bool $forceNew
     *
     * @return Lead
     */
    public function getLeadByTemplate(Lead $template, $forceNew = false)
    {
        if (!$forceNew) {
            /** @var LeadRepository $leadRepository */
            $leadRepository = $this->em->getRepository('MauticLeadBundle:Lead');
            $similarLeads   = $leadRepository->getSimilarLeads($template);
            if (count($similarLeads) !== 0) {
                $bestMatchLead = reset($similarLeads);

                return $this->updateLead($bestMatchLead, $template);
            }
        }
        $this->createLead($template);

        return $template;
    }

    /**
     * @param Lead $template
     */
    private function createLead(Lead $template)
    {
        $template->setNewlyCreated(true);
        $this->leadModel->saveEntity($template, false);
    }

    /**
     * @param Lead $target
     * @param Lead $source
     *
     * @return Lead
     */
    private function updateLead(Lead $target, Lead $source)
    {
        $this->em->flush($target);

        return $target;
    }
}
