<?php

namespace App\Support;

class IndianAmountInWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    public static function rupees(float|int|string $amount): string
    {
        $amount = round((float) $amount, 2);
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);

        $words = 'Rupees '.self::convert($rupees);

        if ($paise > 0) {
            $words .= ' and '.self::convert($paise).' Paise';
        }

        return $words.' Only';
    }

    private static function convert(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $parts = [];

        if ($number >= 10000000) {
            $parts[] = self::convertHundreds((int) floor($number / 10000000)).' Crore';
            $number %= 10000000;
        }
        if ($number >= 100000) {
            $parts[] = self::convertHundreds((int) floor($number / 100000)).' Lakh';
            $number %= 100000;
        }
        if ($number >= 1000) {
            $parts[] = self::convertHundreds((int) floor($number / 1000)).' Thousand';
            $number %= 1000;
        }
        if ($number > 0) {
            $parts[] = self::convertHundreds($number);
        }

        return trim(implode(' ', $parts));
    }

    private static function convertHundreds(int $number): string
    {
        $result = '';

        if ($number >= 100) {
            $result .= self::ONES[(int) floor($number / 100)].' Hundred';
            $number %= 100;
            if ($number > 0) {
                $result .= ' ';
            }
        }

        if ($number >= 20) {
            $result .= self::TENS[(int) floor($number / 10)];
            $number %= 10;
            if ($number > 0) {
                $result .= ' '.self::ONES[$number];
            }
        } elseif ($number > 0) {
            $result .= self::ONES[$number];
        }

        return trim($result);
    }
}
