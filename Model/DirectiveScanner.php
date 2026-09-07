<?php
/**
 * Copyright © Ceymox. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ceymox\StyleSmugglerShield\Model;

/**
 * Detects Magento template directive syntax inside arbitrary strings.
 */
class DirectiveScanner
{
    /**
     * Directives that can instantiate arbitrary blocks, layout handles, config values or templates.
     */
    private const DANGEROUS_DIRECTIVES = [
        'block',
        'layout',
        'template',
        'config',
        'helper',
        'widget',
        'customvar',
    ];

    /**
     * Full directive keyword list, used for the strict "{{keyword" match.
     */
    private const ALL_DIRECTIVES = [
        'block',
        'layout',
        'template',
        'config',
        'helper',
        'widget',
        'customvar',
        'css',
        'inlinecss',
        'protocol',
        'store',
        'media',
        'view',
        'depend',
        'if',
        'ifnull',
        'for',
        'trans',
        'var',
        'legacy',
    ];

    /**
     * Find directive-smuggling attempts in a single string value.
     *
     * @param string $value
     * @return string[]
     */
    public function scanString(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $normalized = $this->normalize($value);
        if (strpos($normalized, '{{') === false) {
            return [];
        }

        return $this->findDirectives($normalized);
    }

    /**
     * Match directive syntax in an already brace-normalized string.
     *
     * @param string $normalized
     * @return string[]
     */
    private function findDirectives(string $normalized): array
    {
        $keywords = implode('|', self::ALL_DIRECTIVES);
        if (preg_match_all('/\{\{\s*(' . $keywords . ')\b.*?\}\}/si', $normalized, $found)) {
            return $found[0];
        }

        // truncated payload, e.g. split across multiple GraphQL arguments
        $dangerousKeywords = implode('|', self::DANGEROUS_DIRECTIVES);
        if (preg_match('/\{\{\s*(' . $dangerousKeywords . ')\b/i', $normalized, $partial)) {
            return [$partial[0]];
        }

        return [];
    }

    /**
     * Recursively scan a decoded structure for directive-smuggling attempts.
     *
     * @param mixed $data
     * @return string[]
     */
    public function scanStructure($data): array
    {
        if (is_string($data)) {
            return $this->scanString($data);
        }

        if (is_array($data)) {
            foreach ($data as $item) {
                $matches = $this->scanStructure($item);
                if ($matches) {
                    return $matches;
                }
            }
        }

        return [];
    }

    /**
     * Recursively strip directive syntax out of string leaves of a decoded structure.
     *
     * @param mixed $data
     * @return mixed
     */
    public function sanitizeStructure($data)
    {
        if (is_string($data)) {
            foreach ($this->scanString($data) as $match) {
                $neutered = str_replace(['{{', '}}'], ['&#123;&#123;', '&#125;&#125;'], $match);
                $data = str_replace($match, $neutered, $data);
            }

            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeStructure($value);
            }
        }

        return $data;
    }

    /**
     * Decode the brace obfuscation tricks used to smuggle "{{" past a naive filter.
     *
     * @param string $value
     * @return string
     */
    private function normalize(string $value): string
    {
        $replacements = [
            '&#123;' => '{',
            '&#x7b;' => '{',
            '&#X7B;' => '{',
            '&lbrace;' => '{',
            '&#125;' => '}',
            '&#x7d;' => '}',
            '&#X7D;' => '}',
            '&rbrace;' => '}',
            '%7b' => '{',
            '%7B' => '{',
            '%7d' => '}',
            '%7D' => '}',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $value);
    }
}
