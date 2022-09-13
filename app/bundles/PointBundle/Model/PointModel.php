<?php

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Entity\IntIdInterface;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\PointRepository;
use Mautic\PointBundle\Event\PointActionEvent;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Event\PointChangeActionExecutedEvent;
use Mautic\PointBundle\Event\PointEvent;
use Mautic\PointBundle\Form\Type\PointType;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class PointModel extends CommonFormModel
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @deprecated https://github.com/mautic/mautic/issues/8229
     *
     * @var MauticFactory
     */
    protected $mauticFactory;

    /**
     * @var ContactTracker
     */
    private $contactTracker;

    public function __construct(
        Session $session,
        IpLookupHelper $ipLookupHelper,
        LeadModel $leadModel,
        MauticFactory $mauticFactory,
        ContactTracker $contactTracker
    ) {
        $this->session            = $session;
        $this->ipLookupHelper     = $ipLookupHelper;
        $this->leadModel          = $leadModel;
        $this->mauticFactory      = $mauticFactory;
        $this->contactTracker     = $contactTracker;
    }

    /**
     * {@inheritdoc}
     *
     * @return PointRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPointBundle:Point');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'point:points';
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Point) {
            throw new MethodNotAllowedHttpException(['Point']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        if (empty($options['pointActions'])) {
            $options['pointActions'] = $this->getPointActions();
        }

        return $formFactory->create(PointType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return Point|null
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new Point();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Point) {
            throw new MethodNotAllowedHttpException(['Point']);
        }

        switch ($action) {
            case 'pre_save':
                $name = PointEvents::POINT_PRE_SAVE;
                break;
            case 'post_save':
                $name = PointEvents::POINT_POST_SAVE;
                break;
            case 'pre_delete':
                $name = PointEvents::POINT_PRE_DELETE;
                break;
            case 'post_delete':
                $name = PointEvents::POINT_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new PointEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }

    /**
     * Gets array of custom actions from bundles subscribed PointEvents::POINT_ON_BUILD.
     *
     * @return mixed
     */
    public function getPointActions()
    {
        static $actions;

        if (empty($actions)) {
            //build them
            $actions = [];
            $event   = new PointBuilderEvent($this->translator);
            $this->dispatcher->dispatch(PointEvents::POINT_ON_BUILD, $event);
            $actions['actions'] = $event->getActions();
            $actions['list']    = $event->getActionList();
            $actions['choices'] = $event->getActionChoices();
        }

        return $actions;
    }

    /**
     * Triggers a specific point change.
     *
     * @param string         $type
     * @param IntIdInterface $eventDetails     passthrough from function triggering action to the callback function
     * @param mixed|null     $typeId           Something unique to the triggering event to prevent  unnecessary duplicate calls
     * @param bool           $allowUserRequest
     *
     * @throws \ReflectionException
     */
    public function triggerAction($type, $eventDetails = null, $typeId = null, Lead $lead = null, $allowUserRequest = false)
    {
        //only trigger actions for not logged Mautic users
        if (!$this->security->isAnonymous() && !$allowUserRequest) {
            return;
        }

        if (null !== $typeId && MAUTIC_ENV === 'prod') {
            //let's prevent some unnecessary DB calls
            $triggeredEvents = $this->session->get('mautic.triggered.point.actions', []);
            if (in_array($typeId, $triggeredEvents)) {
                return;
            }
            $triggeredEvents[] = $typeId;
            $this->session->set('mautic.triggered.point.actions', $triggeredEvents);
        }

        //find all the actions for published points
        /** @var \Mautic\PointBundle\Entity\PointRepository $repo */
        $repo            = $this->getRepository();
        $availablePoints = $repo->getPublishedByType($type);
        $ipAddress       = $this->ipLookupHelper->getIpAddress();

        if (null === $lead) {
            $lead = $this->contactTracker->getContact();

            if (null === $lead || !$lead->getId()) {
                return;
            }
        }

        //get available actions
        $availableActions = $this->getPointActions();

        //get a list of actions that has already been performed on this lead
        $completedActions = $repo->getCompletedLeadActions($type, $lead->getId());

        $persist = [];
        /** @var Point $action */
        foreach ($availablePoints as $action) {
            //make sure the action still exists
            if (!isset($availableActions['actions'][$action->getType()])) {
                continue;
            }

            $settings = $availableActions['actions'][$action->getType()];

            if (isset($settings['eventName'])) {
                // 1. step - can change points from event
                $pointChangeActionExecutedEvent = new PointChangeActionExecutedEvent($action, $lead, $eventDetails, $completedActions);
                $event                          = $this->dispatcher->dispatch($settings['eventName'], $pointChangeActionExecutedEvent);
                if (!$event->canChangePoints()) {
                    continue;
                }
            } else {
                // 2. step - can change points from callback
                if (!$this->invokeCallback($action, $lead, $eventDetails, $settings)) {
                    continue;
                }
                // 3. step - can change points from log
                if (!$action->getRepeatable() && isset($completedActions[$action->getId()])) {
                    continue;
                }
            }

            $this->adjustLeadPoints($action, $lead);

            $event = new PointActionEvent($action, $lead);
            $this->dispatcher->dispatch(PointEvents::POINT_ON_ACTION, $event);

            // Add to log, repeatable is not logged, just executed
            if (!$action->getRepeatable()) {
                $log = new LeadPointLog();
                $log->setIpAddress($ipAddress);
                $log->setPoint($action);
                $log->setLead($lead);
                $log->setInternalId((int) $eventDetails->getId());
                $log->setDateFired(new \DateTime());
                $persist[] = $log;
            }
        }

        if (!empty($persist)) {
            $this->getRepository()->saveEntities($persist);
            // Detach logs to reserve memory
            $this->em->clear('Mautic\PointBundle\Entity\LeadPointLog');
        }

        if (!empty($lead->getpointchanges())) {
            $this->leadModel->saveEntity($lead);
        }
    }

    private function adjustLeadPoints(Point $action, Lead $lead): void
    {
        $delta = $action->getDelta();
        $lead->adjustPoints($delta);
        $parsed = explode('.', $action->getType());
        $lead->addPointsChangeLogEntry(
            $parsed[0],
            $action->getId().': '.$action->getName(),
            $parsed[1],
            $delta,
            $this->ipLookupHelper->getIpAddress()
        );
    }

    /**
     * @depreacated need replace by eventName
     *
     * @param array<string|int> $settings
     *
     * @return bool
     *
     * @throws \ReflectionException
     */
    private function invokeCallback(Point $action, Lead $lead, IntIdInterface $eventDetails, array $settings)
    {
        $callback = (isset($settings['callback'])) ? $settings['callback'] :
            ['\\Mautic\\PointBundle\\Helper\\EventHelper', 'engagePointAction'];

        $args = [
            'action' => [
                'id'         => $action->getId(),
                'type'       => $action->getType(),
                'name'       => $action->getName(),
                'properties' => $action->getProperties(),
                'points'     => $action->getDelta(),
            ],
            'lead'         => $lead,
            'factory'      => $this->mauticFactory, // WHAT?
            'eventDetails' => $eventDetails,
        ];

        if (is_array($callback)) {
            $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        } elseif (false !== strpos($callback, '::')) {
            $parts      = explode('::', $callback);
            $reflection = new \ReflectionMethod($parts[0], $parts[1]);
        } else {
            $reflection = new \ReflectionMethod(null, $callback);
        }

        $pass = [];
        foreach ($reflection->getParameters() as $param) {
            if (isset($args[$param->getName()])) {
                $pass[] = $args[$param->getName()];
            } else {
                $pass[] = null;
            }
        }

        return $reflection->invokeArgs($this, $pass);
    }

    /**
     * Get line chart data of points.
     *
     * @param string $unit          {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * @param string $dateFormat
     * @param array  $filter
     * @param bool   $canViewOthers
     *
     * @return array
     */
    public function getPointLineChartData($unit, \DateTime $dateFrom, \DateTime $dateTo, $dateFormat = null, $filter = [], $canViewOthers = true)
    {
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo);
        $q     = $query->prepareTimeDataQuery('lead_points_change_log', 'date_added', $filter);

        if (!$canViewOthers) {
            $q->join('t', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = t.lead_id')
                ->andWhere('l.owner_id = :userId')
                ->setParameter('userId', $this->userHelper->getUser()->getId());
        }

        $data = $query->loadAndBuildTimeData($q);
        $chart->setDataset($this->translator->trans('mautic.point.changes'), $data);

        return $chart->render();
    }
}
