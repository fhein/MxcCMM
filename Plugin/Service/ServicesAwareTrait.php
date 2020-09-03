<?php

namespace MxcCommons\Plugin\Service;

use MxcCommons\Interop\Container\ContainerInterface;

trait ServicesAwareTrait
{
    /** @var ContainerInterface */
    protected  $services;

    public function setServices(ContainerInterface $services)
    {
        $this->services = $services;
    }
}