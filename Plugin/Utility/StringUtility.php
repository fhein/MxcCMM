<?php

namespace MxcCommons\Plugin\Utility;

use MxcCommons\Stdlib\StringUtils;

class StringUtility
{
    public static function camelCaseToUnderscore($value) {
        return self::camelCaseToSeparator($value, '_');
    }

    public static function underscoreToCamelCase($value) {
        return self::separatorToCamelCase($value, '_');
    }

    public static function camelCaseToDash($value) {
        return self::camelCaseToSeparator($value, '-');
    }

    public static function dashToCamelCase($value) {
        return self::separatorToCamelCase($value, '-');
    }

    public static function toUpperCase(string $value) {
        if (extension_loaded('mbstring')) {
            $result = mb_strtoupper($value, 'UTF-8');
        } else {
            $result =  strtoupper($value);
        }
        return $result;
    }

    public static function toLowerCase(string $value) {
        if (extension_loaded('mbstring')) {
            $result = mb_strtolower($value, 'UTF-8');
        } else {
            $result =  strtolower($value);
        }
        return $result;
    }

    protected static function camelCaseToSeparator($value, string $separator)
    {
        if (! (is_string($value) || is_array($value))) return $value;

        if (StringUtils::hasPcreUnicodeSupport()) {
            $pattern     = ['#(?<=(?:\p{Lu}))(\p{Lu}\p{Ll})#', '#(?<=(?:\p{Ll}|\p{Nd}))(\p{Lu})#'];
            $replacement = [$separator . '\1', $separator . '\1'];
        } else {
            $pattern     = ['#(?<=(?:[A-Z]))([A-Z]+)([A-Z][a-z])#', '#(?<=(?:[a-z0-9]))([A-Z])#'];
            $replacement = ['\1' . $separator . '\2', $separator . '\1'];
        }
        return preg_replace($pattern, $replacement, $value);
    }

    protected static function separatorToCamelCase($value, string $separator)
    {
        if (! is_scalar($value) && ! is_array($value)) {
            return $value;
        }
        // a unicode safe way of converting characters to \x00\x00 notation
        $pregQuotedSeparator = preg_quote($separator, '#');
        if (StringUtils::hasPcreUnicodeSupport()) {
            $patterns = [
                '#(' . $pregQuotedSeparator.')(\P{Z}{1})#u',
                '#(^\P{Z}{1})#u',
            ];
            if (! extension_loaded('mbstring')) {
                $replacements = [
                    static function ($matches) {
                        return strtoupper($matches[2]);
                    },
                    static function ($matches) {
                        return strtoupper($matches[1]);
                    },
                ];
            } else {
                $replacements = [
                    static function ($matches) {
                        return mb_strtoupper($matches[2], 'UTF-8');
                    },
                    static function ($matches) {
                        return mb_strtoupper($matches[1], 'UTF-8');
                    },
                ];
            }
        } else {
            $patterns = [
                '#(' . $pregQuotedSeparator.')([\S]{1})#',
                '#(^[\S]{1})#',
            ];
            $replacements = [
                static function ($matches) {
                    return strtoupper($matches[2]);
                },
                static function ($matches) {
                    return strtoupper($matches[1]);
                },
            ];
        }
        $filtered = $value;
        foreach ($patterns as $index => $pattern) {
            $filtered = preg_replace_callback($pattern, $replacements[$index], $filtered);
        }
        return $filtered;
    }

}