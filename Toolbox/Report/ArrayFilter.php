<?php

namespace MxcCommons\Toolbox\Report;

class ArrayFilter
{
    public function __invoke(array $topic, array $filters)
    {
        if (empty ($topic) || empty($filters)) return [];

        foreach ($filters as $class => $params) {
            $topic = array_filter($topic, [new $class($params), 'filter']);
        }
        return $topic;
    }
}