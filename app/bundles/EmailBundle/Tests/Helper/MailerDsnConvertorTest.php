<?php

namespace Mautic\EmailBundle\Tests\Helper;

use Mautic\EmailBundle\Helper\MailerDsnConvertor;

class MailerDsnConvertorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataConvertArrayToDsnStringProvider
     */
    public function testConvertArrayToDsnString(array $parameters, string $dsn): void
    {
        $convertedDsn = MailerDsnConvertor::convertArrayToDsnString($parameters);
        $this->assertEquals($dsn, $convertedDsn);
    }

    public function dataConvertArrayToDsnStringProvider(): array
    {
        return [
            'smtp://null' => [
                [
                    'mailer_transport' => 'smtp',
                    'mailer_host'      => 'null',
                    'mailer_user'      => null,
                    'mailer_password'  => null,
                    'mailer_port'      => null,
                ],
                'smtp://null',
            ],
        ];
    }
}
