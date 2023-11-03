<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Event\TransportWebhookEvent;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageRepository;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

class PublicControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['show_contact_preferences'] = 1;
        parent::setUp();
    }

    public function testMailerCallbackWhenNoTransportProccessesIt(): void
    {
        $this->client->request('POST', '/mailer/callback');

        Assert::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        Assert::assertSame('No email transport that could process this callback was found', $this->client->getResponse()->getContent());
    }

    public function testMailerCallbackWhenTransportDoesNotProccessIt(): void
    {
        self::getContainer()->get('event_dispatcher')->addListener(EmailEvents::ON_TRANSPORT_WEBHOOK, fn () => null /* exists but does nothing */);
        $this->client->request('POST', '/mailer/callback');

        Assert::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        Assert::assertSame('No email transport that could process this callback was found', $this->client->getResponse()->getContent());
    }

    public function testMailerCallbackWhenTransportProccessesIt(): void
    {
        self::getContainer()->get('event_dispatcher')->addListener(EmailEvents::ON_TRANSPORT_WEBHOOK, fn (TransportWebhookEvent $event) => $event->setResponse(new Response('OK')));
        $this->client->request('POST', '/mailer/callback');

        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        Assert::assertSame('OK', $this->client->getResponse()->getContent());
    }

    public function testUnsubscribeFormActionWithoutTheme(): void
    {
        $form = $this->getForm(null);

        $stat = $this->getStat($form);

        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        self::assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), $crawler->filter('form')->eq(0)->attr('action'));
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testContactPreferencesLandingPageTracking(): void
    {
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead, $this->getPreferencesCenterLandingPage());

        $this->em->flush();

        $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        /** @var PageRepository $pageRepository */
        $pageRepository = $this->em->getRepository(Page::class);
        $this->assertSame(1, $pageRepository->getEntity($stat->getEmail()->getPreferenceCenter()->getId())->getHits(), $this->client->getResponse()->getContent());
    }

    public function testContactPreferencesSaveMessage(): void
    {
        $lead = $this->createLead();
        $stat = $this->getStat(null, $lead);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());
        $this->assertStringContainsString('/email/unsubscribe/tracking_hash_unsubscribe_form_email', $crawler->filter('form')->eq(0)->attr('action'));
        $crawler = $this->client->submitForm('Save');

        $this->assertEquals(1, $crawler->filter('#success-message-text')->count());
        $expectedMessage = self::$container->get('translator')->trans('mautic.email.preferences_center_success_message.text');
        $this->assertEquals($expectedMessage, trim($crawler->filter('#success-message-text')->text(null, false)));
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testUnsubscribeFormActionWithThemeWithoutFormSupport(): void
    {
        $form = $this->getForm('aurora');

        $stat = $this->getStat($form);

        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        self::assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), $crawler->filter('form')->eq(0)->attr('action'));
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testUnsubscribeFormActionWithThemeWithFormSupport(): void
    {
        $form = $this->getForm('blank');

        $stat = $this->getStat($form);

        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        self::assertStringContainsString('form/submit?formId='.$stat->getEmail()->getUnsubscribeForm()->getId(), $crawler->filter('form')->eq(0)->attr('action'));
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    public function testWithoutUnsubscribeFormAction(): void
    {
        $this->getForm('blank');

        $stat = $this->getStat();

        $this->em->flush();

        $crawler = $this->client->request('GET', '/email/unsubscribe/'.$stat->getTrackingHash());

        self::assertStringNotContainsString('form/submit?formId=', $crawler->html());
        $this->assertTrue($this->client->getResponse()->isOk());
    }

    private function getPreferencesCenterLandingPage(): Page
    {
        $page = new Page();
        $page->setTitle('Preference center');
        $page->setAlias('Preference-center');
        $page->setIsPublished(true);
        $page->setIsPreferenceCenter(true);
        $page->setCustomHtml('<html><body>{saveprefsbutton}</body></html>');
        $this->em->persist($page);

        return $page;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getStat(Form $form = null, Lead $lead = null, Page $preferenceCenterPage = null): Stat
    {
        $trackingHash = 'tracking_hash_unsubscribe_form_email';
        $emailName    = 'Test unsubscribe form email';

        $email = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setEmailType('template');
        $email->setUnsubscribeForm($form);
        $email->setPreferenceCenter($preferenceCenterPage);
        $this->em->persist($email);

        // Create a test email stat.
        $stat = new Stat();
        $stat->setTrackingHash($trackingHash);
        $stat->setEmailAddress('john@doe.email');
        $stat->setLead($lead);
        $stat->setDateSent(new \DateTime());
        $stat->setEmail($email);
        $this->em->persist($stat);

        return $stat;
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     */
    protected function getForm(?string $formTemplate): Form
    {
        $formName = 'unsubscribe_test_form';

        $form = new Form();
        $form->setName($formName);
        $form->setAlias($formName);
        $form->setTemplate($formTemplate);
        $this->em->persist($form);

        return $form;
    }

    protected function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setEmail('john@doe.email');
        $this->em->persist($lead);

        return $lead;
    }

    public function testPreviewDisabledByDefault(): void
    {
        $emailName    = 'Test preview email';

        $email = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setEmailType('template');
        $email->setCustomHtml('some content');
        $this->em->persist($email);

        $this->client->request('GET', '/email/preview/'.$email->getId());
        $this->assertTrue($this->client->getResponse()->isNotFound(), $this->client->getResponse()->getContent());

        $email->setPublicPreview(true);
        $this->em->persist($email);

        $this->em->flush();

        $this->client->request('GET', '/email/preview/'.$email->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
    }

    public function testPreviewForExpiredEmail(): void
    {
        $emailName    = 'Test preview email';

        $email = new Email();
        $email->setName($emailName);
        $email->setSubject($emailName);
        $email->setPublishUp(new \DateTime('-2 day'));
        $email->setPublishDown(new \DateTime('-1 day'));
        $email->setEmailType('template');
        $email->setCustomHtml('some content');
        $email->setPublicPreview(true);
        $this->em->persist($email);

        $this->em->flush();

        $this->client->request('GET', '/email/preview/'.$email->getId());
        $this->assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
    }
}
