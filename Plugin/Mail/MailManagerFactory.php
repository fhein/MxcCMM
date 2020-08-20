<?php

namespace MxcCommons\Plugin\Mail;

use MxcCommons\Interop\Container\ContainerInterface;
use MxcCommons\Plugin\Service\AugmentedObjectFactory;

class MailManagerFactory extends AugmentedObjectFactory
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
        return $this->augment($container, new MailManager($config));
    }
}