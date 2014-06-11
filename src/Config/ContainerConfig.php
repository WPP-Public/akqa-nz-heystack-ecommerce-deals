<?php

namespace Heystack\Deals\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class ContainerConfig
 * @author Cam Spiers <cameron@heyday.co.nz>
 * @package Heystack\Deals\Config
 */
class ContainerConfig implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deals');

        $assoc = function ($value) {
            return !is_array($value) || (!empty($value) && !count(array_filter(array_keys($value), 'is_string')));
        };

        $rootNode
            ->children()
                ->arrayNode('deals')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('promotional_message')->defaultValue('')
                            ->end()
                            ->arrayNode('conditions')->isRequired()
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('type')->isRequired()->cannotBeEmpty()->end()
                                        ->variableNode('configuration')->isRequired()
                                            ->validate()
                                                ->ifTrue($assoc)
                                                ->thenInvalid('Configuration must be associative or empty')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('result')->isRequired()->cannotBeEmpty()
                                ->children()
                                    ->scalarNode('type')->isRequired()->end()
                                    ->variableNode('configuration')->isRequired()
                                        ->validate()
                                            ->ifTrue($assoc)
                                            ->thenInvalid('Configuration must be associative or empty')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('deals_db')
                    ->children()
                        ->scalarNode('select')->defaultValue('*')->end()
                        ->scalarNode('from')->isRequired()->end()
                        ->scalarNode('where')->defaultNull()->end()
                    ->end()
                ->end()
                ->scalarNode('coupon_class')->defaultValue('')->end()
            ->end();

        return $treeBuilder;
    }
}
