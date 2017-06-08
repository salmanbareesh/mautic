<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FacebookType.
 */
class MessengerType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'messenger_enabled',
            'yesno_button_group',
            [
                'label' => 'mautic.notification.config.form.notification.enabled',
                'attr'  => [
                    'tooltip' => 'mautic.notification.config.form.notification.enabled.tooltip',
                ],
            ]
        );

        $builder->add(
            'messenger_callback_verify_token',
            'text',
            [
                'label' => 'mautic.notification.config.form.notification.subdomain_name',
                'attr'  => [
                    'tooltip'      => 'mautic.notification.config.form.notification.subdomain_name.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"integration_details_isPublished_1":"checked"}',
                ],
            ]
        );


        $builder->add(
            'messenger_page_access_token',
            'text',
            [
                'label' => 'mautic.notification.config.form.notification.subdomain_name',
                'attr'  => [
                    'tooltip'      => 'mautic.notification.config.form.notification.subdomain_name.tooltip',
                    'class'        => 'form-control',
                    'data-show-on' => '{"integration_details_isPublished_1":"checked"}',

                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'messenger_facebook';
    }
}
