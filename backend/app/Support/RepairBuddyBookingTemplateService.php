<?php

namespace App\Support;

class RepairBuddyBookingTemplateService
{
    public function render(string $template, array $pairs): string
    {
        $out = $template;

        foreach ($pairs as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            $replacement = is_scalar($value) || $value === null ? (string) ($value ?? '') : '';

            $simple = '{'.$key.'}';
            $mustache = '{{'.$key.'}}';

            $out = str_replace($simple, $replacement, $out);
            $out = str_replace($mustache, $replacement, $out);

            $quoted = preg_quote($key, '/');
            $out = preg_replace('/{{\s*'.$quoted.'\s*}}/u', $replacement, $out) ?? $out;
        }

        return $out;
    }
}
