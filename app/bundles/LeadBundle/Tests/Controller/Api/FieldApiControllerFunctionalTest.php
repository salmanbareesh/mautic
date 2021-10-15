<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Field\Command\CreateCustomFieldCommand;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @IgnoreAnnotation("covers")
 */
final class FieldApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['create_custom_field_in_background'] = 'testFieldApiEndpointsWithBackgroundProcessingEnabled' === $this->getName();

        parent::setUp();
    }

    public function testCreatingMultiselectField()
    {
        $payload = [
            'label'               => 'Shops (TB)',
            'alias'               => 'shops',
            'type'                => 'multiselect',
            'isPubliclyUpdatable' => true,
            'isUniqueIdentifier'  => false,
            'properties'          => [
                'list' => [
                    ['label' => 'label1', 'value' => 'value1'],
                    ['label' => 'label2', 'value' => 'value2'],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/fields/contact/new', $payload);
        $clientResponse = $this->client->getResponse();
        $fieldResponse  = json_decode($clientResponse->getContent(), true);

        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        Assert::assertTrue($fieldResponse['field']['isPublished']);
        Assert::assertGreaterThan(0, $fieldResponse['field']['id']);
        Assert::assertSame($payload['label'], $fieldResponse['field']['label']);
        Assert::assertSame($payload['alias'], $fieldResponse['field']['alias']);
        Assert::assertSame($payload['type'], $fieldResponse['field']['type']);
        Assert::assertSame($payload['isPubliclyUpdatable'], $fieldResponse['field']['isPubliclyUpdatable']);
        Assert::assertSame($payload['isUniqueIdentifier'], $fieldResponse['field']['isUniqueIdentifier']);
        Assert::assertSame($payload['properties'], $fieldResponse['field']['properties']);

        // Cleanup
        $this->client->request(Request::METHOD_DELETE, '/api/fields/contact/'.$fieldResponse['field']['id'].'/delete', $payload);
        $clientResponse = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    /**
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::saveEntity
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::newEntityAction
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::editEntityAction
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::deleteEntityAction
     * @covers \Mautic\LeadBundle\Field\Command\CreateCustomFieldCommand::execute
     */
    public function testFieldApiEndpointsWithBackgroundProcessingEnabled(): void
    {
        $alias   = uniqid('field');
        $payload = $this->getCreatePayload($alias);
        $id      = $this->assertCreateResponse($payload, Response::HTTP_ACCEPTED);

        // Exeucte the command to create the field
        $this->executeCommand($id);

        // Test fetching
        $this->assertGetResponse($payload, $id);

        // Test editing
        $payload = $this->getEditPayload($id);
        $this->assertPatchResponse($payload, $id, $alias);

        // Test deleting
        $this->assertDeleteResponse($payload, $id, $alias);
    }

    /**
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::saveEntity
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::newEntityAction
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::editEntityAction
     * @covers \Mautic\LeadBundle\Controller\Api\FieldApiController::deleteEntityAction
     */
    public function testFieldApiEndpointsWithBackgroundProcessingDisabled(): void
    {
        $alias   = uniqid('field');
        $payload = $this->getCreatePayload($alias);
        $id      = $this->assertCreateResponse($payload, Response::HTTP_CREATED);

        // Test fetching
        $this->assertGetResponse($payload, $id);

        // Test editing
        $payload = $this->getEditPayload($id);
        $this->assertPatchResponse($payload, $id, $alias);

        // Test deleting
        $this->assertDeleteResponse($payload, $id, $alias);
    }

    private function assertCreateResponse(array $payload, int $expectedStatusCode): int
    {
        // Test creating a new field
        $this->client->request('POST', '/api/fields/contact/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        // should be "accepted" if pushed to the background; otherwise "created"
        $this->assertEquals($expectedStatusCode, $clientResponse->getStatusCode());

        // Assert that the fields returned are what is expected
        foreach ($payload as $key => $value) {
            $this->assertTrue(isset($response['field'][$key]));

            if (Response::HTTP_ACCEPTED === $expectedStatusCode && 'isPublished' === $key) {
                // This should be false because the background job publishes once ready
                $this->assertEquals(false, $response['field'][$key]);
                continue;
            }

            $this->assertEquals($value, $response['field'][$key]);
        }

        return $response['field']['id'];
    }

    private function assertGetResponse(array $payload, int $id): void
    {
        // Test get and that the field was published
        $this->client->request('GET', sprintf('/api/fields/contact/%s', $id));
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());

        // Assert that the fields returned are what is expected and the field is now published
        foreach ($payload as $key => $value) {
            $this->assertTrue(isset($response['field'][$key]));
            $this->assertEquals($value, $response['field'][$key]);
        }
    }

    private function assertPatchResponse(array $payload, int $id, string $alias): void
    {
        $this->client->request('PATCH', sprintf('/api/fields/contact/%s/edit', $id), $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $response = json_decode($clientResponse->getContent(), true);

        // Assert that the fields returned are what is expected noting that certain fields should not be editable
        foreach ($payload as $key => $value) {
            $this->assertTrue(isset($response['field'][$key]));

            switch ($key) {
                case 'alias':
                    $this->assertEquals($alias, $response['field'][$key]);
                    break;

                case 'object':
                    $this->assertEquals('lead', $response['field'][$key]);
                    break;

                case 'type':
                    $this->assertEquals('text', $response['field'][$key]);
                    break;

                default:
                    $this->assertEquals($value, $response['field'][$key]);
            }
        }
    }

    private function assertDeleteResponse(array $payload, int $id, string $alias): void
    {
        // Test the field is deleted
        $this->client->request('DELETE', sprintf('/api/fields/contact/%s/delete', $id));
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode());
        $response = json_decode($clientResponse->getContent(), true);

        // Assert that the fields returned are what is expected
        foreach ($payload as $key => $value) {
            // use array has key because ID will now be null
            $this->assertArrayHasKey($key, $response['field']);

            switch ($key) {
                case 'id':
                    // ID is expected to now be null
                    $this->assertNull($response['field'][$key]);
                    break;

                case 'alias':
                    $this->assertEquals($alias, $response['field'][$key]);
                    break;

                case 'object':
                    $this->assertEquals('lead', $response['field'][$key]);
                    break;

                case 'type':
                    $this->assertEquals('text', $response['field'][$key]);
                    break;

                default:
                    $this->assertEquals($value, $response['field'][$key]);
            }
        }
    }

    private function executeCommand(int $id): void
    {
        // Test that the command will create the field
        $input  = new ArrayInput(['--id' => $id]);
        $output = new BufferedOutput();

        /** @var CreateCustomFieldCommand $command */
        $command    = $this->client->getKernel()->getContainer()->get('mautic.lead.command.create_custom_field');
        $returnCode = $command->run($input, $output);
        $this->assertEquals(0, $returnCode);
    }

    private function getCreatePayload(string $alias): array
    {
        return [
            'isPublished'         => true,
            'label'               => 'New Field',
            'alias'               => $alias,
            'type'                => 'text',
            'group'               => 'core',
            'object'              => 'lead',
            'defaultValue'        => 'foobar',
            'isRequired'          => true,
            'isPubliclyUpdatable' => true,
            'isUniqueIdentifier'  => true,
            'isVisible'           => false,
            'isListable'          => false,
            'properties'          => [],
        ];
    }

    private function getEditPayload(int $id): array
    {
        return [
            'id'                  => $id,
            'label'               => 'Foo Bar',
            'alias'               => 'should_not_change',
            'type'                => 'text',
            'group'               => 'core',
            'object'              => 'company',
            'defaultValue'        => 'foobar',
            'isRequired'          => false,
            'isPubliclyUpdatable' => false,
            'isUniqueIdentifier'  => false,
            'isVisible'           => true,
            'isListable'          => true,
            'properties'          => [],
        ];
    }
}
