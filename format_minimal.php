<?php
require_once 'MinimalFormatter.class.php';

/**
 * Фасад/Обертка для старой функции format_minimal.
 */
function format_minimal(string $s): string
{
    $formatter = new MinimalFormatter();
    return $formatter->format($s);
}
