<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Form\Type;

use Mautic\CampaignBundle\Form\Type\CampaignListType;
use Mautic\LeadBundle\Form\Type\CompanyListType;
use Mautic\LeadBundle\Form\Type\LeadListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array>
 */
class DashboardBestTrackingPagesType extends AbstractType
{
    /**
     * @param FormBuilderInterface<string|FormBuilderInterface> $builder
     * @param mixed[]                                           $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'companyId',
            CompanyListType::class,
            [
                'label'       => 'mautic.email.companyId.filter',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'empty_data'  => null,
                'required'    => false,
                'multiple'    => false,
                'modal_route' => null, // disable "Add new" option in ajax lookup
            ]
        );
        $builder->add(
            'campaignId',
            CampaignListType::class,
            [
                'label'       => 'mautic.email.campaignId.filter',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'empty_data'  => null,
                'placeholder' => '',
                'required'    => false,
                'multiple'    => false,
            ]
        );
        $builder->add(
            'segmentId',
            LeadListType::class,
            [
                'label'      => 'mautic.email.segmentId.filter',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'empty_data' => null,
                'required'   => false,
            ]
        );
    }
}
