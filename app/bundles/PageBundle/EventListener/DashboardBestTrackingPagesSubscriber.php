<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\EventListener\DashboardSubscriber as MainDashboardSubscriber;
use Mautic\PageBundle\Form\Type\DashboardBestTrackingPagesType;
use Mautic\PageBundle\Model\PageModel;

class DashboardBestTrackingPagesSubscriber extends MainDashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s).
     *
     * @var string
     */
    protected $bundle  = 'page';

    /**
     * Define the widget(s).
     *
     * @var array<string,array<string, string>>
     */
    protected $types = [
        'best.tracking.pages' => [
            'formAlias' => DashboardBestTrackingPagesType::class,
        ],
    ];

    /**
     * DashboardSubscriber constructor.
     */
    public function __construct(protected PageModel $pageModel)
    {
    }

    /**
     * Set a widget detail when needed.
     */
    public function onWidgetDetailGenerate(WidgetDetailEvent $event): void
    {
        if ('best.tracking.pages' == $event->getType()) {
            $widget = $event->getWidget();
            $params = $widget->getParams();

            if (!$event->isCached()) {
                $items = [];
                $pages = $this->pageModel->getPopularTrackedPages($widget->getLimitCalcByWeight(), $params['dateFrom'], $params['dateTo'], $params);
                // Build table rows with links
                foreach ($pages as $page) {
                    $row = [
                        [
                            'value'     => $page['url_title'],
                            'type'      => 'link',
                            'external'  => true,
                            'link'      => $page['url'],
                        ],
                        [
                            'value' => $page['hits'],
                        ],
                    ];
                    $items[] = $row;
                }
                $event->setTemplateData([
                    'headItems' => [
                        'mautic.dashboard.label.title',
                        'mautic.dashboard.label.hits',
                    ],
                    'bodyItems' => $items,
                ]);
            }

            $event->setTemplate('@MauticCore/Helper/table.html.twig');
            $event->stopPropagation();
        }
    }
}
