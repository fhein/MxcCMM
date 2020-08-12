<?php

namespace MxcCommons\Plugin\Service;

trait ClassConfigAwareTrait
{
    /** @var array */
    protected $classConfig;

    public function setClassConfig(array $classConfig)
    {
        $this->classConfig = $classConfig;
    }
}