<?php

namespace Mautic\PageBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\PublishDownDateType;
use Mautic\CoreBundle\Form\Type\PublishUpDateType;
use Mautic\CoreBundle\Form\Type\ThemeListType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\ThemeHelperInterface;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Helper\PageConfigInterface;
use Mautic\PageBundle\Model\PageModel;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PageType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var PageModel
     */
    private $model;

    /**
     * @var User
     */
    private $user;

    /**
     * @var PageConfigInterface
     */
    private $pageConfig;

    /**
     * @var bool
     */
    private $canViewOther = false;

    /**
     * @var ThemeHelperInterface
     */
    private $themeHelper;

    public function __construct(
        EntityManager $entityManager,
        PageModel $pageModel,
        CorePermissions $corePermissions,
        UserHelper $userHelper,
        ThemeHelperInterface $themeHelper,
        PageConfigInterface $pageConfig
    ) {
        $this->em           = $entityManager;
        $this->model        = $pageModel;
        $this->canViewOther = $corePermissions->isGranted('page:pages:viewother');
        $this->user         = $userHelper->getUser();
        $this->themeHelper  = $themeHelper;
        $this->pageConfig   = $pageConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['content' => 'html', 'customHtml' => 'html', 'redirectUrl' => 'url', 'headScript' => 'html', 'footerScript' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('page.page', $options));

        $builder->add(
            'title',
            TextType::class,
            [
                'label'      => 'mautic.core.title',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $html = $options['data']->getCustomHtml();
        if ($this->pageConfig->isDraftEnabled() && !empty($options['data']->getId()) && $options['data']->hasDraft() && !empty($options['data']->getDraft()->getHtml())) {
            $html = $options['data']->getDraft()->getHtml();
        }
        $builder->add(
            'customHtml',
            TextareaType::class,
            [
                'label'    => 'mautic.page.form.customhtml',
                'required' => false,
                'attr'     => [
                    'tooltip'              => 'mautic.page.form.customhtml.help',
                    'class'                => 'form-control editor-builder-tokens builder-html',
                    'data-token-callback'  => 'page:getBuilderTokens',
                    'data-token-activator' => '{',
                    'rows'                 => '25',
                ],
                'data'     => $html,
            ]
        );

        $template = $options['data']->getTemplate() ?? 'blank';
        if ($this->pageConfig->isDraftEnabled() && !empty($options['data']->getId()) && $options['data']->hasDraft() && !empty($options['data']->getDraft()->getTemplate())) {
            $template = $options['data']->getDraft()->getTemplate();
        }
        $builder->add(
            'template',
            ThemeListType::class,
            [
                'feature' => 'page',
                'attr'    => [
                    'class'   => 'form-control not-chosen hidden',
                    'tooltip' => 'mautic.page.form.template.help',
                ],
                'placeholder' => 'mautic.core.none',
                'data'        => $template,
            ]
        );

        $builder->add('isPublished', YesNoButtonGroupType::class);

        $builder->add(
            'isPreferenceCenter',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.page.form.preference_center',
                'data'  => $options['data']->isPreferenceCenter() ? $options['data']->isPreferenceCenter() : false,
                'attr'  => [
                    'tooltip' => 'mautic.page.form.preference_center.tooltip',
                ],
            ]
        );

        $builder->add(
            'noIndex',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.page.config.no_index',
                'data'  => $options['data']->getNoIndex() ? $options['data']->getNoIndex() : false,
            ]
        );

        $builder->add('publishUp', PublishUpDateType::class);
        $builder->add('publishDown', PublishDownDateType::class);
        $builder->add('sessionId', HiddenType::class);

        // Custom field for redirect URL
        $this->model->getRepository()->setCurrentUser($this->user);

        $redirectUrlDataOptions = '';
        $pages                  = $this->model->getRepository()->getPageList('', 0, 0, $this->canViewOther, 'variant', [$options['data']->getId()]);
        foreach ($pages as $page) {
            $redirectUrlDataOptions .= "|{$page['alias']}";
        }

        $transformer = new IdToEntityModelTransformer($this->em, Page::class);
        $builder->add(
            $builder->create(
                'variantParent',
                HiddenType::class
            )->addModelTransformer($transformer)
        );

        $builder->add(
            $builder->create(
                'translationParent',
                PageListType::class,
                [
                    'label'      => 'mautic.core.form.translation_parent',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.core.form.translation_parent.help',
                    ],
                    'required'    => false,
                    'multiple'    => false,
                    'placeholder' => 'mautic.core.form.translation_parent.empty',
                    'top_level'   => 'translation',
                    'ignore_ids'  => [(int) $options['data']->getId()],
                ]
            )->addModelTransformer($transformer)
        );

        $formModifier = function (FormInterface $form, $isVariant) {
            if ($isVariant) {
                $form->add(
                    'variantSettings',
                    VariantType::class,
                    [
                        'label' => false,
                    ]
                );
            }
        };

        // Building the form
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier(
                    $event->getForm(),
                    $event->getData()->getVariantParent()
                );
            }
        );

        // After submit
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $data = $event->getData();
                if (isset($data['variantParent'])) {
                    $formModifier(
                        $event->getForm(),
                        $data['variantParent']
                    );
                }
            }
        );

        $builder->add(
            'metaDescription',
            TextareaType::class,
            [
                'label'      => 'mautic.page.form.metadescription',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control', 'maxlength' => 160],
                'required'   => false,
            ]
        );

        $builder->add(
            'headScript',
            TextareaType::class,
            [
                'label'      => 'mautic.page.form.headscript',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                    'rows'  => '8',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'footerScript',
            TextareaType::class,
            [
                'label'      => 'mautic.page.form.footerscript',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                    'rows'  => '8',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'redirectType',
            RedirectListType::class,
            [
                'feature' => 'page',
                'attr'    => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.form.redirecttype.help',
                ],
                'placeholder' => 'mautic.page.form.redirecttype.none',
            ]
        );

        $builder->add(
            'redirectUrl',
            UrlType::class,
            [
                'required'   => true,
                'label'      => 'mautic.page.form.redirecturl',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class'        => 'form-control',
                    'maxlength'    => 200,
                    'tooltip'      => 'mautic.page.form.redirecturl.help',
                    'data-toggle'  => 'field-lookup',
                    'data-action'  => 'page:fieldList',
                    'data-target'  => 'redirectUrl',
                    'data-options' => $redirectUrlDataOptions,
                ],
            ]
        );

        $builder->add(
            'alias',
            TextType::class,
            [
                'label'      => 'mautic.core.alias',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.help.alias',
                ],
                'required' => false,
            ]
        );

        // add category
        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'page',
            ]
        );

        $builder->add(
            'language',
            LocaleType::class,
            [
                'label'      => 'mautic.core.language',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.page.form.language.help',
                ],
                'required'   => true,
            ]
        );

        $extraButtons['pre_extra_buttons'] = [
            [
                'name'  => 'builder',
                'label' => 'mautic.core.builder',
                'attr'  => [
                    'class'   => 'btn btn-default btn-dnd btn-nospin btn-builder text-primary',
                    'icon'    => 'fa fa-cube',
                    'onclick' => "Mautic.launchBuilder('page');",
                ],
            ],
        ];

        $draftActionButtons = $this->getDraftActionButtons($options['data']);
        if (!empty($draftActionButtons)) {
            $extraButtons['post_extra_buttons'] = $draftActionButtons;
        }
        $builder->add('buttons',
            FormButtonsType::class,
            $extraButtons
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    private function getDraftActionButtons(Page $page): array
    {
        $draftActionButtons = [];
        if (!$this->pageConfig->isDraftEnabled() || empty($page->getId())) {
            return $draftActionButtons;
        }

        if ($page->hasDraft()) {
            $draftActionButtons[] = [
                'name'  => 'apply_draft',
                'label' => 'mautic.core.applydraft',
                'type'  => SubmitType::class,
                'attr'  => [
                    'class'   => 'btn btn-default text-primary btn-apply-draft',
                    'icon'    => 'fa fa-files-o text-success',
                ],
            ];
            $draftActionButtons[] = [
                'name'  => 'discard_draft',
                'label' => 'mautic.core.discarddraft',
                'type'  => SubmitType::class,
                'attr'  => [
                    'class'   => 'btn btn-default text-primary btn-discard-draft',
                    'icon'    => 'fa fa-trash text-danger',
                ],
            ];
        } else {
            $draftActionButtons[] = [
                'name'  => 'save_draft',
                'label' => 'mautic.core.saveasdraft',
                'type'  => SubmitType::class,
                'attr'  => [
                    'class'   => 'btn btn-default text-primary btn-save-draft',
                    'icon'    => 'fa fa-file text-success',
                ],
            ];
        }

        return $draftActionButtons;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
        ]);
    }
}
