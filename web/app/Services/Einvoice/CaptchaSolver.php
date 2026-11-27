<?php

namespace App\Services\Einvoice;

/**
 * Port of detectSVGCaptcha / ListPathAllKeywords (frmLoginHDDT.vb).
 * Each character in the portal's SVG captcha is drawn as one <path>; its command sequence
 * (M/Q/Z letters only) identifies the character. Characters are ordered by their starting X.
 */
class CaptchaSolver
{
    // Index 0..25 = A..Z, 26..35 = digits 0..9. Empty = character never used by the portal.
    private const SIGNATURES = [
        'MQQQQQZMQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQZMQQZ',                                   // A
        'MQQQQQQQQQZMQQQQQQZMQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQZMQQQQQQQQZMQQQQQQQQZ',       // B
        'MQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQZ',                              // C
        'MQQQQQQQQZMQQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQZ',                               // D
        'MQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQZ',                              // E
        'MQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQZ',                                        // F
        'MQQQQQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ',          // G
        'MQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQZ',                                    // H
        '',                                                                             // I
        'MQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQZ',                                           // J
        'MQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQZ',                                          // K
        '',                                                                             // L
        'MQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQZ',                             // M
        'MQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQZ',                                           // N
        '',                                                                             // O
        'MQQQQQQZMQQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQZ',                                // P
        'MQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQZ',        // Q
        'MQQQQQQZMQQQQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQZ',                              // R
        'MQQQQQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ',              // S
        'MQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQZ',                                             // T
        '',                                                                             // U
        'MQQQQQQQQQQZMQQQQQQQQQQQQQQQQZ',                                                 // V
        'MQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQZ',                             // W
        'MQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQZ',                                           // X
        'MQQQQQQQQQZMQQQQQQQQQQQQQZ',                                                     // Y
        'MQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQZ',                                      // Z
        '',                                                                             // 0
        '',                                                                             // 1
        'MQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ',                        // 2
        'MQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ', // 3
        'MQQQQZMQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQZMQQQQQZ',                             // 4
        'MQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQQQQQQQQQZ',                         // 5
        'MQQQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQZ',                  // 6
        'MQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQQZ',                                    // 7
        'MQQQQQQQQZMQQQQQQQZMQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQZMQQQQQQQZ', // 8
        'MQQQQQQQQZMQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQQQQQQQQQQZMQQQQQQQQQQQZ',               // 9
    ];

    /** Returns the guessed text, or '' when no character is recognised. */
    public function solve(string $svg): string
    {
        $lookup = [];
        foreach (self::SIGNATURES as $index => $signature) {
            if ($signature !== '') {
                $lookup[$signature] = $index < 26 ? chr(65 + $index) : (string) ($index - 26);
            }
        }

        preg_match_all('/\sd="([^"]*)"/', $svg, $paths);

        $found = [];
        foreach ($paths[1] as $path) {
            $signature = strtoupper(preg_replace('/([MQZ])[^MQZ]*/i', '$1', $path));
            if (! isset($lookup[$signature])) {
                continue; // noise line or unknown character
            }
            // Starting X of the path (number right after the first "M")
            preg_match('/^\s*M\s*(-?[\d.]+)/i', $path, $start);
            $found[] = ['x' => (float) ($start[1] ?? 0), 'char' => $lookup[$signature]];
        }

        usort($found, fn ($a, $b) => $a['x'] <=> $b['x']);

        return implode('', array_column($found, 'char'));
    }
}
