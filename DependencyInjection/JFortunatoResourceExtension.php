<?php

namespace JFortunato\ResourceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class JFortunatoResourceExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $resources = isset($config['resources']) ? $config['resources']:array();

        // set the resource voter config argument
        $container->setParameter('jfortunato.access.resource_voter.resource_config', $resources);

        // create the RESTful resource controllers
        $this->createControllerServices($resources, $container);
    }

    protected function createControllerServices(array $resources, ContainerBuilder $container)
    {
        foreach ($resources as $name => $config) {
            // set the form type service first, we'll use it in the controller
            $container->setDefinition(sprintf('jfortunato.form.%s', $name), new Definition($config['form_type']));

            $definition = new Definition($config['controller']);
            $definition->setArguments(array(
                $config['entity'],
                new Reference('jfortunato.form.' . $name),
                new Reference('jfortunato.manager.resource'),
                new Reference('form.factory'),
                new Reference('security.context'),
                new Reference('fos_rest.view_handler'),
            ));

            $container->setDefinition(sprintf('jfortunato.controller.%s', $name), $definition);
        }
    }
}
