<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support\Locale;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\Locale\BloodType;

final class BloodTypeTest extends TestCase
{
    public function testBloodTypeCountsAreCorrect(): void
    {
        $this->assertCount(4, BloodType::ABO);
        $this->assertCount(4, BloodType::ABO_GROUP_EN);
        $this->assertCount(2, BloodType::RH);
    }

    public function testBloodTypeFirstAndLastValuesMatch(): void
    {
        $this->assertSame('A', BloodType::ABO[0]);
        $this->assertSame('AB', BloodType::ABO[3]);
        $this->assertSame('Rh+', BloodType::RH[0]);
        $this->assertSame('Rh-', BloodType::RH[1]);
    }
}
