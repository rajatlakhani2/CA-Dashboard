<?php

namespace Tests\Unit\Support;

use App\Support\IndianAmountInWords;
use PHPUnit\Framework\TestCase;

class IndianAmountInWordsTest extends TestCase
{
    public function test_zero_rupees(): void
    {
        $this->assertStringContainsString('Zero', IndianAmountInWords::rupees(0));
        $this->assertStringEndsWith('Only', IndianAmountInWords::rupees(0));
    }

    public function test_simple_amount(): void
    {
        $words = IndianAmountInWords::rupees(1180);
        $this->assertStringContainsString('Thousand', $words);
        $this->assertStringContainsString('Only', $words);
    }

    public function test_amount_with_paise(): void
    {
        $words = IndianAmountInWords::rupees(100.75);
        $this->assertStringContainsString('Paise', $words);
    }

    public function test_lakh_notation(): void
    {
        $words = IndianAmountInWords::rupees(125000);
        $this->assertStringContainsString('Lakh', $words);
    }
}
