<?php

namespace JFortunato\ResourceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jfortunato_resource');

        $rootNode
            ->children()
                ->arrayNode('resources')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('entity')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('controller')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('form_type')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('access_control')->isRequired()
                                ->children()
                                    ->arrayNode('default')->addDefaultsIfNotSet()
                                        ->children()
                                            ->booleanNode('owner')->defaultFalse()->end()
                                            ->scalarNode('role')->defaultValue('ROLE_SUPER_ADMIN')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('index')
                                        ->children()
                                            ->booleanNode('owner')->end()
                                            ->scalarNode('role')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('show')
                                        ->children()
                                            ->booleanNode('owner')->end()
                                            ->scalarNode('role')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('create')
                                        ->children()
                                            ->booleanNode('owner')->end()
                                            ->scalarNode('role')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('update')
                                        ->children()
                                            ->booleanNode('owner')->end()
                                            ->scalarNode('role')->end()
                                        ->end()
                                    ->end()

                                    ->arrayNode('delete')
                                        ->children()
                                            ->booleanNode('owner')->end()
                                            ->scalarNode('role')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
