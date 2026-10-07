<?php

namespace Tests\Feature\Foundation;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CurrencyInputTest extends TestCase
{
    public function test_currency_input_displays_indonesian_rupiah_thousands_separator_and_keeps_raw_model_value(): void
    {
        $html = Blade::render('<x-currency-input model="amount" value="100000" />');

        $this->assertStringContainsString('value="100.000"', $html);
        $this->assertStringContainsString('value="100000"', $html);
        $this->assertStringContainsString('wire:model.live.debounce.250ms="amount"', $html);
        $this->assertStringContainsString('inputmode="numeric"', $html);
    }
}
