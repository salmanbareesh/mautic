<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EmailSubscriber.
 */
class OwnerSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private static $ownerFieldSprintf = '{ownerfield=%s}';

    /** @var LeadModel */
    private $leadModel;

    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_BUILD   => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND    => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailDisplay', 0],
        ];
    }

    /**
     * @param EmailBuilderEvent $event
     */
    public function onEmailBuild(EmailBuilderEvent $event)
    {
        $event->addToken(self::buildToken('email'), 'Owner Email');
        $event->addToken(self::buildToken('first_name'), 'Owner First Name');
        $event->addToken(self::buildToken('last_name'), 'Owner Last Name');
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailDisplay(EmailSendEvent $event)
    {
        $event->addToken(self::buildToken('email'), '[Owner Email]');
        $event->addToken(self::buildToken('first_name'), '[Owner First Name]');
        $event->addToken(self::buildToken('last_name'), '[Owner Last Name]');
    }

    /**
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event)
    {
        $contact = $event->getLead();

        if (isset($contact['owner_id']) === false) {
            $this->addEmptyTokens($event);

            return;
        }

        $owner = $this->leadModel->getRepository()->getLeadOwner($contact['owner_id']);
        if ($owner === false) {
            $this->addEmptyTokens($event);

            return;
        }

        $event->addToken(self::buildToken('email'), (string) $owner['email']);
        $event->addToken(self::buildToken('first_name'), (string) $owner['first_name']);
        $event->addToken(self::buildToken('last_name'), (string) $owner['last_name']);
    }

    /**
     * Used to replace all owner tokens with emptiness so not to email out tokens.
     *
     * @param EmailSendEvent $event
     */
    private function addEmptyTokens(EmailSendEvent $event)
    {
        $event->addToken(self::buildToken('email'), '');
        $event->addToken(self::buildToken('first_name'), '');
        $event->addToken(self::buildToken('last_name'), '');
    }

    private static function buildToken($field)
    {
        return sprintf(self::$ownerFieldSprintf, $field);
    }
}
