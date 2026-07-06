<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_format_currency_formats_pkr(): void
    {
        $this->assertEquals('Rs. 1,500.00', format_currency(1500));
    }

    public function test_format_currency_supports_other_currencies(): void
    {
        $this->assertEquals('$ 99.50', format_currency(99.5, 'USD'));
    }

    public function test_hijri_year_approximation_is_in_valid_range(): void
    {
        $year = hijri_year(Carbon::create(2026, 7, 1));

        // 2026 CE ≈ 1447 AH; must satisfy the app's 1400–1500 validation range.
        $this->assertEquals(1447, $year);
    }

    public function test_calculate_days_between(): void
    {
        $this->assertEquals(
            9,
            calculate_days_between(Carbon::create(2026, 7, 1), Carbon::create(2026, 7, 10))
        );
    }

    public function test_percentage_change_handles_zero_base(): void
    {
        $this->assertEquals(0, percentage_change(0, 50));
        $this->assertEquals(0, percentage_change(0, 0));
        $this->assertEquals(50.0, percentage_change(100, 150));
    }

    public function test_haram_category_names_are_flagged(): void
    {
        $this->assertFalse(is_halal_category('Interest Income'));
        $this->assertFalse(is_halal_category('Lottery Winnings'));
        $this->assertTrue(is_halal_category('Groceries'));
    }
}
