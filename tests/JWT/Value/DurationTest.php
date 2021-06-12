<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Value;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Kreait\Firebase\JWT\Value\Duration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class DurationTest extends TestCase
{
    public function testItCanBeNone(): void
    {
        $now = new DateTimeImmutable();
        $this->assertEquals($now, $now->add(Duration::none()->value()));
    }

    /**
     * @dataProvider validValues
     *
     * @param mixed $value
     * @param mixed $expectedSpec
     */
    public function testItParsesAValue($value, $expectedSpec): void
    {
        $this->assertSame($expectedSpec, (string) Duration::make($value));
    }

    /**
     * @return array<string, array<array-key, string|DateInterval|Duration>>
     */
    public function validValues(): array
    {
        return [
            'DateInterval Spec ("P1DT1H")' => ['P1DT1H', 'P1DT1H'],
            'DateInterval("PT24H")' => [new DateInterval('PT24H'), 'P1D'],
            'Duration("PT24H")' => [Duration::make('PT24H'), 'P1D'],
            'too verbose' => [Duration::make('P0Y0M0DT0H0M3600S'), 'PT1H'],
        ];
    }

    /**
     * @dataProvider invalidValues
     *
     * @param DateInterval|int|string $value
     */
    public function testItRejectsInvalidValues($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        Duration::make($value);
    }

    /**
     * @return array<string, array<int, DateInterval|int|string>>
     */
    public function invalidValues(): array
    {
        $negativeInterval = new DateInterval('PT1H');
        $negativeInterval->invert = 1;

        return [
            'negative seconds' => [-1],
            'invalid spec' => ['P1H'], // should be PT1H
            'negative interval' => [$negativeInterval],
            'something stupid' => ['something stupid'],
        ];
    }

    public function testItOptimizesTheDateIntervalSpec(): void
    {
        $this->assertSame('P1DT1H', (string) Duration::make('PT24H60M'));
    }

    public function testItCanBeCompared(): void
    {
        $given = Duration::make('60 minutes');
        $equal = Duration::make('1 hour');
        $larger = Duration::make('61 minutes');
        $smaller = Duration::make('59 minutes');
        $this->assertTrue($given->equals($equal));
        $this->assertTrue($given->isLargerThan($smaller));
        $this->assertTrue($given->isSmallerThan($larger));
    }

    public function testItCanBeCastedToADateIntervalSpecString(): void
    {
        $this->assertSame('PT1H', (string) Duration::make('1 hour'));
    }
}
