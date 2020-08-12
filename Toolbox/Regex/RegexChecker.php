<?php

namespace MxcCommons\Toolbox\Regex;

class RegexChecker
{
    protected $errors;

    public function getErrors()
    {
        return $this->errors;
    }

    public function validate($regexes)
    {
        if (is_string($regexes)) {
            $regexes = [$regexes];
        }
        if (! is_array($regexes)) {
            return false;
        }
        $this->errors = [];
        foreach($regexes as $regex) {
            if (preg_replace($regex, '', 'test') === null) {
                $this->errors[] = $regex;
            }
        }
        return empty($this->errors);
    }
}