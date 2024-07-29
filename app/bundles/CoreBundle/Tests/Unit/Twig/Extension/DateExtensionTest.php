<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Extension\DateExtension;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\TwigFunction;

class DateExtensionTest extends TestCase
{
    private DateHelper $dateHelper;
    private DateExtension $dateExtension;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->dateHelper = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $translator,
            $coreParametersHelper
        );

        $this->dateExtension = new DateExtension($this->dateHelper);
    }

    public function testGetFunctions(): void
    {
        $functions = $this->dateExtension->getFunctions();

        $this->assertContainsOnlyInstancesOf(TwigFunction::class, $functions);
        $this->assertCount(8, $functions);

        $functionNames = array_map(function (TwigFunction $function) {
            return $function->getName();
        }, $functions);

        $this->assertContains('dateToText', $functionNames);
        $this->assertContains('dateToFull', $functionNames);
        $this->assertContains('dateToFullConcat', $functionNames);
        $this->assertContains('dateToDate', $functionNames);
        $this->assertContains('dateToTime', $functionNames);
        $this->assertContains('dateToShort', $functionNames);
        $this->assertContains('dateFormatRange', $functionNames);
        $this->assertContains('dateToHumanized', $functionNames);
    }

    public function testToText(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $result = $this->dateExtension->toText($datetime, 'UTC', 'Y-m-d H:i:s', true);
        $this->assertStringContainsString('December 31, 2023', $result);
    }

    public function testToHumanized(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $result = $this->dateExtension->toHumanized($datetime, 'UTC', 'Y-m-d H:i:s');
        $this->assertStringContainsString('ago', $result);
    }

    public function testToFull(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $result = $this->dateExtension->toFull($datetime, 'UTC', 'Y-m-d H:i:s');
        $this->assertStringContainsString('December 31, 2023', $result);
    }

    public function testToFullConcat(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $result = $this->dateExtension->toFullConcat($datetime, 'UTC', 'Y-m-d H:i:s');
        $this->assertStringContainsString('2023', $result);
        $this->assertStringContainsString('23:59', $result);
    }

    public function testToDate(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $result = $this->dateExtension->toDate($datetime, 'UTC', 'Y-m-d H:i:s');
        $this->assertStringContainsString('2023', $result);
    }

    public function testToTime(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $result = $this->dateExtension->toTime($datetime, 'UTC', 'Y-m-d H:i:s');
        $this->assertStringContainsString('23:59', $result);
    }

    public function testToShort(): void
    {
        $datetime = '2023-12-31 23:59:59';
        $result = $this->dateExtension->toShort($datetime, 'UTC', 'Y-m-d H:i:s');
        $this->assertStringContainsString('Dec', $result);
    }

    public function testFormatRange(): void
    {
        $range = new \DateInterval('P1Y2M3DT4H5M6S');
        $result = $this->dateExtension->formatRange($range);
        $this->assertStringContainsString('year', $result);
        $this->assertStringContainsString('month', $result);
        $this->assertStringContainsString('day', $result);
    }
}