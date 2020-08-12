<?php

namespace MxcCommons\Toolbox\Strings;

class StringTool
{
    // makes replaces all commata with dots before converting to float
    public static function tofloat(string $float)
    {
        return floatval(str_replace(',', '.', $float));
    }

    public static function dbQuote($value) {
        if (is_array($value)) {
            return array_map('self::quote', $value);
        }
        return self::quote($value);
    }

    private static function quote($value) {
        switch(gettype($value)) {
            case 'boolean':
                return $value ? 1 : 0;
            case 'string':
                return '\'' . $value . '\'';
            case 'NULL':
                return 'NULL';
            case 'double':
                return sprintf('%f',$value);
            case 'integer':
                return (string) $value;
        }
    }
}