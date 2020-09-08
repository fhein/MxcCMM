<?php


namespace MxcCommons\Toolbox\Config;


class Config
{
    public static function toFile(string $fn, array $data)
    {
        $text = "<?php\n\nreturn " . var_export($data, true) . ";";
        file_put_contents($fn, $text);
    }

    public static function fromFile(string $fn)
    {
        $config = [];
        if (file_exists($fn)) {
            $config = include $fn;
        }
        return $config;
    }

}