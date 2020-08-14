<?php

namespace MxcCommons\Toolbox\Report\Filter;

class ContainsAllOf
{
    protected $patterns;

    public function __construct(array $patterns) {
        $this->patterns = $patterns;
    }

    public function filter($value) {
        foreach ($this->patterns as $pattern) {
            if (strpos($value, $pattern) === false) return false;
        }
        return true;
    }
}