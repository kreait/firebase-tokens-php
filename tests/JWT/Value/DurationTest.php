<?php

declare(strict_types=1);

namespace Kreait\Firebase\JWT\Tests\Value;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Kreait\Firebase\JWT\Value\Duration;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    /** @test */
    public function it_can_be_none()
    {
        $now = new DateTimeImmutable();
        $this->assertEquals($now, $now->add(Duration::none()->value()));
    }

    /**
     * @test
     * @dataProvider validValues
     */
    public function it_parses_a_value($value, $expectedSpec)
    {
        $this->assertSame($expectedSpec, (string) Duration::make($value));
    }

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
     * @test
     * @dataProvider invalidValues
     */
    public function it_rejects_invalid_values($value)
    {
        $this->expectException(InvalidArgumentException::class);
        Duration::make($value);
    }

    public function invalidValues(): array
    {
        $negativeInterval = new DateInterval('PT1H');
        $negativeInterval->invert = 1;

        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'negative seconds' => [-1],
            'invalid spec' => ['P1H'], // should be PT1H
            'negative interval' => [$negativeInterval],
        ];
    }

    /** @test */
    public function it_optimizes_the_date_interval_spec()
    {
        $this->assertSame('P1DT1H', (string) Duration::make('PT24H60M'));
    }

    /** @test */
    public function it_can_be_compared()
    {
        $given = Duration::make('60 minutes');
        $equal = Duration::make('1 hour');
        $larger = Duration::make('61 minutes');
        $smaller = Duration::make('59 minutes');
        $this->assertTrue($given->equals($equal));
        $this->assertTrue($given->isLargerThan($smaller));
        $this->assertTrue($given->isSmallerThan($larger));
    }

    /** @test */
    public function it_can_be_casted_to_a_date_interval_spec_string()
    {
        $this->assertSame('PT1H', (string) Duration::make('1 hour'));
    }
}
