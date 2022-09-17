<?php

namespace Mautic\EmailBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Controller\VariantAjaxControllerTrait;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\PlainTextHelper;
use Mautic\EmailBundle\Mailer\EmailSender;
use Mautic\EmailBundle\Mailer\Exception\ConnectionErrorException;
use Mautic\EmailBundle\Mailer\Transport\TestConnectionInterface;
use Mautic\EmailBundle\Mailer\Transport\TransportWrapper;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\PageBundle\Form\Type\AbTestPropertiesType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Address;

class AjaxController extends CommonAjaxController
{
    use VariantAjaxControllerTrait;
    use AjaxLookupControllerTrait;

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getAbTestFormAction(Request $request)
    {
        return $this->getAbTestForm(
            $request,
            'email',
            AbTestPropertiesType::class,
            'email_abtest_settings',
            'emailform',
            'MauticEmailBundle:AbTest:form.html.php',
            ['MauticEmailBundle:AbTest:form.html.php', 'MauticEmailBundle:FormTheme\Email']
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function sendBatchAction(Request $request)
    {
        $dataArray = ['success' => 0];

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model    = $this->getModel('email');
        $objectId = $request->request->get('id', 0);
        $pending  = $request->request->get('pending', 0);
        $limit    = $request->request->get('batchlimit', 100);

        if ($objectId && $entity = $model->getEntity($objectId)) {
            $dataArray['success'] = 1;
            $session              = $this->container->get('session');
            $progress             = $session->get('mautic.email.send.progress', [0, (int) $pending]);
            $stats                = $session->get('mautic.email.send.stats', ['sent' => 0, 'failed' => 0, 'failedRecipients' => []]);
            $inProgress           = $session->get('mautic.email.send.active', false);

            if ($pending && !$inProgress && $entity->isPublished()) {
                $session->set('mautic.email.send.active', true);
                list($batchSentCount, $batchFailedCount, $batchFailedRecipients) = $model->sendEmailToLists($entity, null, $limit);

                $progress[0] += ($batchSentCount + $batchFailedCount);
                $stats['sent'] += $batchSentCount;
                $stats['failed'] += $batchFailedCount;

                foreach ($batchFailedRecipients as $emails) {
                    $stats['failedRecipients'] = $stats['failedRecipients'] + $emails;
                }

                $session->set('mautic.email.send.progress', $progress);
                $session->set('mautic.email.send.stats', $stats);
                $session->set('mautic.email.send.active', false);
            }

            $dataArray['percent']  = ($progress[1]) ? ceil(($progress[0] / $progress[1]) * 100) : 100;
            $dataArray['progress'] = $progress;
            $dataArray['stats']    = $stats;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Called by parent::getBuilderTokensAction().
     *
     * @param $query
     *
     * @return array
     */
    protected function getBuilderTokens($query)
    {
        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');

        return $model->getBuilderComponents(null, ['tokens'], $query, false);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function generatePlaintTextAction(Request $request)
    {
        $custom = $request->request->get('custom');
        $id     = $request->request->get('id');

        $parser = new PlainTextHelper(
            [
                'base_url' => $request->getSchemeAndHttpHost().$request->getBasePath(),
            ]
        );

        $dataArray = [
            'text' => $parser->setHtml($custom)->getText(),
        ];

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getAttachmentsSizeAction(Request $request)
    {
        $assets = $request->get('assets', [], true);
        $size   = 0;
        if ($assets) {
            /** @var \Mautic\AssetBundle\Model\AssetModel $assetModel */
            $assetModel = $this->getModel('asset');
            $size       = $assetModel->getTotalFilesize($assets);
        }

        return $this->sendJsonResponse(['size' => $size]);
    }

    /**
     * Tests monitored email connection settings.
     *
     * @return JsonResponse
     */
    protected function testMonitoredEmailServerConnectionAction(Request $request)
    {
        $dataArray = ['success' => 0, 'message' => ''];

        if ($this->user->isAdmin()) {
            $settings = $request->request->all();

            if (empty($settings['password'])) {
                $existingMonitoredSettings = $this->coreParametersHelper->get('monitored_email');
                if (is_array($existingMonitoredSettings) && (!empty($existingMonitoredSettings[$settings['mailbox']]['password']))) {
                    $settings['password'] = $existingMonitoredSettings[$settings['mailbox']]['password'];
                }
            }

            /** @var \Mautic\EmailBundle\MonitoredEmail\Mailbox $helper */
            $helper = $this->factory->getHelper('mailbox');

            try {
                $helper->setMailboxSettings($settings, false);
                $folders = $helper->getListingFolders('');
                if (!empty($folders)) {
                    $dataArray['folders'] = '';
                    foreach ($folders as $folder) {
                        $dataArray['folders'] .= "<option value=\"$folder\">$folder</option>\n";
                    }
                }
                $dataArray['success'] = 1;
                $dataArray['message'] = $this->translator->trans('mautic.core.success');
            } catch (\Exception $e) {
                $dataArray['message'] = $this->translator->trans($e->getMessage());
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Tests mail transport settings.
     *
     * @return JsonResponse
     */
    protected function testEmailServerConnectionAction(Request $request)
    {
        $dataArray = ['success' => 0, 'message' => ''];
        $user      = $this->get('mautic.helper.user')->getUser();

        if ($user->isAdmin()) {
            $settings      = $request->request->all();

            /** @var TransportWrapper $transportWrapper */
            $transportWrapper = $this->container->get('mautic.email.transport_wrapper');

            try {
                /** @var TestConnectionInterface $extension */
                $extension = $transportWrapper->getTransportExtension($settings['transport']);
                if (!($extension instanceof TestConnectionInterface)) {
                    $dataArray['message'] = 'Transport doesn\'t support testing connection.';

                    return $this->sendJsonResponse($dataArray);
                }
            } catch (\LogicException $exception) {
                $dataArray['message'] = 'Transport is not found.';

                return $this->sendJsonResponse($dataArray);
            }

            try {
                if ($extension->testConnection(new Dsn($settings['transport'], $settings['host'], null, null, $settings['port'] ? (int) $settings['port'] : null))) {
                    $dataArray['success'] = 1;
                    $dataArray['message'] = $this->translator->trans('mautic.core.success');
                }
            } catch (ConnectionErrorException $exception) {
                $dataArray['message'] = $exception->getMessage();
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    protected function sendTestEmailAction(Request $request)
    {
        /** @var Translator $translator */
        $translator = $this->get('translator');

        $user         = $this->get('mautic.helper.user')->getUser();
        $userFullName = trim($user->getFirstName().' '.$user->getLastName());
        if (empty($userFullName)) {
            $userFullName = '';
        }
        /** @var EmailSender $emailSender */
        $emailSender = $this->get('mautic.email.mailer.email_sender');
        $success     = 1;
        $message     = $translator->trans('mautic.core.success');
        try {
            $emailSender->sendTestEmail(new Address($user->getEmail(), $userFullName));
        } catch (\Exception $exception) {
            $success = 0;
            $message = $exception->getMessage();
        }

        return $this->sendJsonResponse(['success' => $success, 'message' => $message]);
    }

    protected function getEmailCountStatsAction(Request $request)
    {
        /** @var EmailModel $model */
        $model = $this->getModel('email');

        $id  = $request->get('id');
        $ids = $request->get('ids');

        // Support for legacy calls
        if (!$ids && $id) {
            $ids = [$id];
        }

        $data = [];
        foreach ($ids as $id) {
            if ($email = $model->getEntity($id)) {
                $pending = $model->getPendingLeads($email, null, true);
                $queued  = $model->getQueuedCounts($email);

                $data[] = [
                    'id'          => $id,
                    'pending'     => 'list' === $email->getEmailType() && $pending ? $this->translator->trans(
                        'mautic.email.stat.leadcount',
                        ['%count%' => $pending]
                    ) : 0,
                    'queued'      => ($queued) ? $this->translator->trans('mautic.email.stat.queued', ['%count%' => $queued]) : 0,
                    'sentCount'   => $this->translator->trans('mautic.email.stat.sentcount', ['%count%' => $email->getSentCount(true)]),
                    'readCount'   => $this->translator->trans('mautic.email.stat.readcount', ['%count%' => $email->getReadCount(true)]),
                    'readPercent' => $this->translator->trans('mautic.email.stat.readpercent', ['%count%' => $email->getReadPercentage(true)]),
                ];
            }
        }

        // Support for legacy calls
        if ($request->get('id') && !empty($data[0])) {
            $data = $data[0];
        } else {
            $data = [
                'success' => 1,
                'stats'   => $data,
            ];
        }

        return new JsonResponse($data);
    }
}
