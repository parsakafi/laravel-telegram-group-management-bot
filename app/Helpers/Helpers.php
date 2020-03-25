<?php

use Illuminate\Support\Facades\Config;

/**
 * Converts English digits to Persian digits
 *
 * @param  string  $number  Numbers
 *
 * @return string Formatted numbers
 */
function faNumber($number)
{
    return str_replace(
        range(0, 9),
        array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'),
        $number
    );
}

function localizeString($str)
{
    $locale = Config::get('app.locale');
    if($locale == 'fa') {
        $str = faNumber($str);
        $str = str_replace(['*', '\*'], ['×'], $str);
    }

    return $str;
}

function escapeMarkdown($text)
{
    $markdown = [
        '#',
        '*',
        '_',
        // ... rest of markdown entities
    ];

    $replacements = [
        '\#',
        '\*',
        '\_',
        // ... rest of corresponding escaped markdown
    ];

    return str_replace($markdown, $replacements, $text);
}