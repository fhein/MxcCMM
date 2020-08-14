<?php

namespace MxcCommons\Toolbox\Report;

class ArrayMap
{
    public function __invoke(array $topic, array $mappers)
    {
        if (empty ($topic) || empty($mappers)) return [];

        foreach ($mappers as $class => $params) {
            $topic = array_map([new $class($params), 'map'], $topic);
        }
        return $topic;
    }
}