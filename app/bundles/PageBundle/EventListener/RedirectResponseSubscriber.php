<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\PageBundle\Event\RedirectResponseEvent;
use Mautic\PageBundle\Helper\RedirectHelper;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RedirectResponseSubscriber implements EventSubscriberInterface
{
    private RedirectHelper $redirectHelper;

    public function __construct(RedirectHelper $redirectHelper)
    {
        $this->redirectHelper = $redirectHelper;
    }

    /**
     * @return array<array<string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::ON_REDIRECT_RESPONSE => ['onRedirect', 0],
        ];
    }

    public function onRedirect(RedirectResponseEvent $redirectResponseEvent): void
    {
        $redirectResponse = $this->redirectHelper->trackedRedirect($redirectResponseEvent->getRedirect());
        $redirectResponseEvent->setRedirectResponse($redirectResponse);
    }
}
