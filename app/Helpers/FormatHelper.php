<?php

if (!function_exists('format_currency')) {
    function format_currency($value): string
    {
        $value = (float) $value;
        if ($value < 0) {
            return '-Rp ' . number_format(abs($value), 0, ',', '.');
        }
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
}

if (!function_exists('format_number')) {
    function format_number($value): string
    {
        $value = (float) $value;
        $decimals = ($value == floor($value)) ? 0 : 2;
        $formatted = number_format($value, $decimals, ',', '.');
        return $formatted;
    }
}
