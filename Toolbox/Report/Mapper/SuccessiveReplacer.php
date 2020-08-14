<?php

namespace MxcCommons\Toolbox\Report\Mapper;

class SuccessiveReplacer
{
    protected $replacements;
    protected $replacer;
    protected $reportUnmatchedPatterns;

    public function __construct(array $params) {
        $this->replacements = $params['replacements'] ?? [];
        $this->replacer = $params['replacer'] ?? null;
        $this->reportUnmatchedPatterns = $params['reportUnmatchedPatterns'] ?? false;
    }

    public function map($value) {
        if (! $this->replacer) return $value;
        $current = $value;
        $value = [];
        $value['value'] = $current;
        $value['replacements'] = [];
        if ($this->reportUnmatchedPatterns) $value['not_matched'] = [];
        foreach ($this->replacements as $search => $replace) {
            $new = ($this->replacer)($search, $replace, $current);
            if ($new !== $current) {
                $value['replacements'][] = [
                    'srch' => $search,
                    'repl' => $replace,
                    'rslt' => $new,
                ];
                $current = $new;
            } elseif ($this->reportUnmatchedPatterns) {
                $value['not_matched'][] = $search;
            }
        }
        return $value;
    }
}