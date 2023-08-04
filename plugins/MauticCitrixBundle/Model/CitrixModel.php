<?php

namespace MauticPlugin\MauticCitrixBundle\Model;

use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticCitrixBundle\CitrixEvents;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEvent;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventRepository;
use MauticPlugin\MauticCitrixBundle\Entity\CitrixEventTypes;
use MauticPlugin\MauticCitrixBundle\Event\CitrixEventUpdateEvent;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixHelper;
use MauticPlugin\MauticCitrixBundle\Helper\CitrixProducts;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @extends FormModel<CitrixEvent>
 */
class CitrixModel extends FormModel
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var EventModel
     */
    protected $eventModel;

    public function __construct(LeadModel $leadModel, EventModel $eventModel)
    {
        $this->leadModel  = $leadModel;
        $this->eventModel = $eventModel;
    }

    /**
     * {@inheritdoc}
     *
     * @return CitrixEventRepository
     */
    public function getRepository()
    {
        $result = $this->em->getRepository(CitrixEvent::class);
        \assert($result instanceof CitrixEventRepository);

        return $result;
    }

    /**
     * @param string    $product
     * @param string    $email
     * @param string    $eventName
     * @param string    $eventDesc
     * @param Lead      $lead
     * @param string    $eventType
     * @param \DateTime $eventDate
     * @param string    $joinURL
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function addEvent($product, $email, $eventName, $eventDesc, $eventType, $lead, \DateTime $eventDate = null, $joinURL = null)
    {
        if (!CitrixProducts::isValidValue($product) || !CitrixEventTypes::isValidValue($eventType)) {
            CitrixHelper::log('addEvent: incorrect data');

            return;
        }
        $citrixEvent = new CitrixEvent();
        $citrixEvent->setProduct($product);
        $citrixEvent->setEmail($email);
        $citrixEvent->setEventName($eventName);
        $citrixEvent->setEventDesc($eventDesc);
        $citrixEvent->setEventType($eventType);
        $citrixEvent->setLead($lead);

        if (null !== $eventDate) {
            $citrixEvent->setEventDate($eventDate);
        }

        if (null !== $joinURL) {
            $citrixEvent->setEventDesc($eventDesc.'_!'.$joinURL);
        }

        $this->em->persist($citrixEvent);
        $this->em->flush();
    }

    /**
     * @param string $product
     * @param string $email
     *
     * @return array
     */
    public function getEventsByLeadEmail($product, $email)
    {
        if (!CitrixProducts::isValidValue($product)) {
            return []; // is not a valid citrix product
        }

        return $this->getRepository()->findByEmail($product, $email);
    }

    /**
     * @param string $product
     * @param string $eventName
     * @param string $eventType
     *
     * @return array
     */
    public function getEmailsByEvent($product, $eventName, $eventType)
    {
        if (!CitrixProducts::isValidValue($product) || !CitrixEventTypes::isValidValue($eventType)) {
            return []; // is not a valid citrix product
        }
        $citrixEvents = $this->getRepository()->findBy(
            [
                'product'   => $product,
                'eventName' => $eventName,
                'eventType' => $eventType,
            ]
        );

        $emails = [];
        if (0 !== count($citrixEvents)) {
            $emails = array_map(
                function (CitrixEvent $citrixEvent) {
                    return $citrixEvent->getEmail();
                },
                $citrixEvents
            );
        }

        return $emails;
    }

    /**
     * @param string $product
     *
     * @return array
     */
    public function getDistinctEventNames($product)
    {
        if (!CitrixProducts::isValidValue($product)) {
            return []; // is not a valid citrix product
        }
        $dql = sprintf(
            "SELECT DISTINCT(c.eventName) FROM MauticCitrixBundle:CitrixEvent c WHERE c.product='%s'",
            $product
        );
        $query = $this->em->createQuery($dql);
        $items = $query->getResult();

        return array_map(
            function ($item) {
                return array_pop($item);
            },
            $items
        );
    }

    /**
     * @param string $product
     *
     * @return array
     */
    public function getDistinctEventNamesDesc($product)
    {
        if (!CitrixProducts::isValidValue($product)) {
            return []; // is not a valid citrix product
        }
        $dql = sprintf(
            "SELECT DISTINCT c.eventName, c.eventDesc FROM MauticCitrixBundle:CitrixEvent c WHERE c.product='%s'",
            $product
        );
        $query  = $this->em->createQuery($dql);
        $items  = $query->getResult();
        $result = [];
        foreach ($items as $item) {
            $eventDesc = $item['eventDesc'];
            // strip joinUrl if exists
            $pos = strpos($eventDesc, '_!');
            if (false !== $pos) {
                $eventDesc = substr($eventDesc, 0, $pos);
            }
            // filter events with same id
            $eventId = $item['eventName'];
            $pos     = strpos($eventId, '_#');
            $eventId = substr($eventId, $pos);
            foreach ($result as $k => $v) {
                if (false !== strpos($k, $eventId)) {
                    unset($result[$k]);
                }
            }
            $result[$item['eventName']] = $eventDesc;
        }

        return $result;
    }

    /**
     * @param string $product
     * @param string $email
     * @param string $eventType
     *
     * @return int
     */
    public function countEventsBy($product, $email, $eventType, array $eventNames = [])
    {
        if (!CitrixProducts::isValidValue($product) || !CitrixEventTypes::isValidValue($eventType)) {
            return 0; // is not a valid citrix product
        }
        $dql = 'SELECT COUNT(c.id) as cant FROM MauticCitrixBundle:CitrixEvent c '.
                  ' WHERE c.product=:product and c.email=:email AND c.eventType=:eventType ';

        if (0 !== count($eventNames)) {
            $dql .= 'AND c.eventName IN (:eventNames)';
        }

        $query = $this->em->createQuery($dql);
        $query->setParameters([
            ':product'   => $product,
            ':email'     => $email,
            ':eventType' => $eventType,
        ]);
        if (0 !== count($eventNames)) {
            $query->setParameter(':eventNames', $eventNames);
        }

        return (int) $query->getResult()[0]['cant'];
    }

    /**
     * @param mixed                $product
     * @param mixed                $productId
     * @param mixed                $eventName
     * @param mixed                $eventDesc
     * @param int                  $count
     * @param OutputInterface|null $output
     */
    public function syncEvent($product, $productId, $eventName, $eventDesc, &$count = 0, $output = null)
    {
        $registrants      = CitrixHelper::getRegistrants($product, $productId);
        $knownRegistrants = $this->getEmailsByEvent(
            $product,
            $eventName,
            CitrixEventTypes::REGISTERED
        );

        list($registrantsToAdd, $registrantsToDelete) = $this->filterEventContacts($registrants, $knownRegistrants);
        $count += $this->batchAddAndRemove(
            $product,
            $eventName,
            $eventDesc,
            CitrixEventTypes::REGISTERED,
            $registrantsToAdd,
            $registrantsToDelete,
            $output
        );
        unset($registrants, $knownRegistrants, $registrantsToAdd, $registrantsToDelete);

        $attendees      = CitrixHelper::getAttendees($product, $productId);
        $knownAttendees = $this->getEmailsByEvent(
            $product,
            $eventName,
            CitrixEventTypes::ATTENDED
        );

        list($attendeesToAdd, $attendeesToDelete) = $this->filterEventContacts($attendees, $knownAttendees);
        $count += $this->batchAddAndRemove(
            $product,
            $eventName,
            $eventDesc,
            CitrixEventTypes::ATTENDED,
            $attendeesToAdd,
            $attendeesToDelete,
            $output
        );
        unset($attendees, $knownAttendees, $attendeesToAdd, $attendeesToDelete);
    }

    /**
     * @param string          $product
     * @param string          $eventName
     * @param string          $eventDesc
     * @param string          $eventType
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function batchAddAndRemove(
        $product,
        $eventName,
        $eventDesc,
        $eventType,
        array $contactsToAdd = [],
        array $emailsToRemove = [],
        OutputInterface $output = null
    ) {
        if (!CitrixProducts::isValidValue($product) || !CitrixEventTypes::isValidValue($eventType)) {
            return 0;
        }

        $count       = 0;
        $newEntities = [];

        // Add events
        if (0 !== count($contactsToAdd)) {
            $searchEmails = array_keys($contactsToAdd);
            $leads        = array_change_key_case(
                $this->leadModel->getRepository()->getLeadsByFieldValue('email', $searchEmails, null, true),
                CASE_LOWER
            );

            foreach ($contactsToAdd as $email => $info) {
                if (!isset($leads[strtolower($email)])) {
                    $lead = (new Lead())
                        ->addUpdatedField('email', $info['email'])
                        ->addUpdatedField('firstname', $info['firstname'])
                        ->addUpdatedField('lastname', $info['lastname']);
                    $this->leadModel->saveEntity($lead);

                    $leads[strtolower($email)] = $lead;
                }

                $citrixEvent = new CitrixEvent();
                $citrixEvent->setProduct($product);
                $citrixEvent->setEmail($email);
                $citrixEvent->setEventName($eventName);
                $citrixEvent->setEventDesc($eventDesc);
                $citrixEvent->setEventType($eventType);
                $citrixEvent->setLead($leads[$email]);

                if (!empty($info['event_date'])) {
                    $citrixEvent->setEventDate($info['event_date']);
                }

                if (!empty($info['joinUrl'])) {
                    $citrixEvent->setEventDesc($eventDesc.'_!'.$info['joinUrl']);
                }

                $newEntities[] = $citrixEvent;

                if (null !== $output) {
                    $output->writeln(
                        ' + '.$email.' '.$eventType.' to '.
                        substr($citrixEvent->getEventName(), 0, 40).((strlen(
                                $citrixEvent->getEventName()
                            ) > 40) ? '...' : '.')
                    );
                }
                ++$count;
            }

            $this->getRepository()->saveEntities($newEntities);
        }

        // Delete events
        if (0 !== count($emailsToRemove)) {
            $citrixEvents = $this->getRepository()->findBy(
                [
                    'eventName' => $eventName,
                    'eventType' => $eventType,
                    'email'     => $emailsToRemove,
                    'product'   => $product,
                ]
            );
            $this->getRepository()->deleteEntities($citrixEvents);

            /** @var CitrixEvent $citrixEvent */
            foreach ($citrixEvents as $citrixEvent) {
                if (null !== $output) {
                    $output->writeln(
                        ' - '.$citrixEvent->getEmail().' '.$eventType.' from '.
                        substr($citrixEvent->getEventName(), 0, 40).((strlen(
                                $citrixEvent->getEventName()
                            ) > 40) ? '...' : '.')
                    );
                }
                ++$count;
            }
        }

        if (0 !== count($newEntities)) {
            /** @var CitrixEvent $entity */
            foreach ($newEntities as $entity) {
                if ($this->dispatcher->hasListeners(CitrixEvents::ON_CITRIX_EVENT_UPDATE)) {
                    $citrixEvent = new CitrixEventUpdateEvent($product, $eventName, $eventDesc, $eventType, $entity->getLead());
                    $this->dispatcher->dispatch($citrixEvent, CitrixEvents::ON_CITRIX_EVENT_UPDATE);
                    unset($citrixEvent);
                }
            }
        }

        $this->em->clear(Lead::class);
        $this->em->clear(CitrixEvent::class);

        return $count;
    }

    /**
     * @param $found
     * @param $known
     *
     * @return array
     */
    private function filterEventContacts($found, $known)
    {
        // Lowercase the emails to keep things consistent
        $known  = array_map('strtolower', $known);
        $delete = array_diff($known, array_map('strtolower', array_keys($found)));
        $add    = array_filter(
            $found,
            function ($key) use ($known) {
                return !in_array(strtolower($key), $known);
            },
            ARRAY_FILTER_USE_KEY
        );

        return [$add, $delete];
    }
}