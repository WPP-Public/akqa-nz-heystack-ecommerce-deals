<?php

namespace Heystack\Subsystem\Deals\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * Class ContainerConfig
 * @author Cam Spiers <cameron@heyday.co.nz>
 * @package Heystack\Subsystem\Deals\Config
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
                                        ->variableNode('configuration')->isRequired()->cannotBeEmpty()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('result')->isRequired()->cannotBeEmpty()
                                ->children()
                                    ->scalarNode('type')->isRequired()->end()
                                    ->variableNode('configuration')->isRequired()->cannotBeEmpty()->end()
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
            ->end();

        return $treeBuilder;
    }
}
