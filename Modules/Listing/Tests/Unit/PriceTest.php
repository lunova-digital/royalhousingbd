<?php

namespace Modules\Listing\Tests\Unit;

use Modules\Listing\Support\Price;
use PHPUnit\Framework\TestCase;

class PriceTest extends TestCase
{
    public function test_parses_displayed_prices_into_taka(): void
    {
        $this->assertSame(45_000_000, Price::parse('৳ 4.5 Crore'));
        $this->assertSame(3_500_000, Price::parse('৳ 35 Lakh / katha'));
        $this->assertSame(2_500_000, Price::parse('25 lac per share'));
        $this->assertSame(12_000_000, Price::parse('Tk 1,20,00,000'));
        $this->assertSame(85_000, Price::parse('85000'));
        $this->assertSame(20_000_000, Price::parse('২ কোটি টাকা'));
        $this->assertSame(1_000_000_000, Price::parse('100 crore'));
    }

    public function test_sizes_and_blanks_are_not_prices(): void
    {
        $this->assertNull(Price::parse(''));
        $this->assertNull(Price::parse(null));
        $this->assertNull(Price::parse('3 katha'));
        $this->assertNull(Price::parse('Call for price'));
        $this->assertNull(Price::parse('1'));
    }

    public function test_formats_and_bounds(): void
    {
        $this->assertSame(0, Price::bound('abc'));
        $this->assertSame(0, Price::bound(-5));
        $this->assertSame(Price::MAX, Price::bound(Price::MAX + 1));
        $this->assertSame(5_000_000, Price::bound('5000000'));
        $this->assertSame(Price::MAX, Price::STEPS[count(Price::STEPS) - 1]);
        $this->assertSame(Price::STEPS, array_values(array_unique(Price::STEPS)));
        $sorted = Price::STEPS;
        sort($sorted);
        $this->assertSame($sorted, Price::STEPS);
    }
}
