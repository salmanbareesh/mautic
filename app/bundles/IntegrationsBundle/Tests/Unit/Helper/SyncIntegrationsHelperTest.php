<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Helper;

use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\SyncInterface;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;

class SyncIntegrationsHelperTest extends TestCase
{
    public function testHasObjectSyncEnabled(): void
    {
        $mauticObject           = 'some_integration';
        $integrationsHelperMock = $this->createMock(IntegrationsHelper::class);

        $syncInterfaceMock = $this->createMock(SyncInterface::class);
        $syncInterfaceMock->method('getName')->willReturn($mauticObject);

        $integrationMock = $this->createMock(Integration::class);
        $integrationsHelperMock->method('getIntegrationConfiguration')
            ->with($syncInterfaceMock)
            ->willReturn($integrationMock);
        $integrationMock->method('getIsPublished')->willReturn(true);
        $supportedFeatures = [
            'integration' => [
                'syncDateFrom'       => '2021-09-01',
                'syncStrategies'     => [
                    'Lead' => [
                        'operator' => null,
                        'field'    => 'IsUnreadByOwner',
                    ],
                ],
                'syncStrategiesPush' => [
                    'lead' => [
                        'operator' => null,
                        'field'    => null,
                    ],
                ],
                'activitySync'       => null,
                'activityEvents'     => [
                    'point.gained',
                    'form.submitted',
                    'email.read',
                ],
                'sandbox'            => [],
                'namespace'          => null,
            ],
            'sync'        => [
                'objects'       => [
                    'Lead',
                ],
                'fieldMappings' => [
                    'Lead' => [
                        'Company'  => [
                            'mappedField'   => 'company',
                            'syncDirection' => 'integration',
                        ],
                        'Email'    => [
                            'mappedField'   => 'email',
                            'syncDirection' => 'bidirectional',
                        ],
                        'LastName' => [
                            'mappedField'   => 'lastname',
                            'syncDirection' => 'mautic',
                        ],
                    ],
                ],
            ],
        ];
        $integrationMock->method('getSupportedFeatures')->willReturn($supportedFeatures);

        $syncInterfaceMock->method('getIntegrationConfiguration')->willReturn($integrationMock);

        $objectProviderMock = $this->createMock(ObjectProvider::class);

        $syncIntegrationsHelper = new SyncIntegrationsHelper($integrationsHelperMock, $objectProviderMock);
        $syncIntegrationsHelper->addIntegration($syncInterfaceMock);
        $syncIntegrationsHelper->hasObjectSyncEnabled($mauticObject);
    }
}
