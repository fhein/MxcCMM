<?php

namespace MxcCommons\Plugin\Mail;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\ServiceManager\Factory\FactoryInterface;

class MailManagerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $config = @$config['mail_templates'] ?? [];
        return new MailManager($config);
    }
}