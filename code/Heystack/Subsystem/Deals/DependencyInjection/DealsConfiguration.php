<?php

namespace Heystack\Subsystem\Deals\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class DealsConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('deals');
		
		$rootNode
			->prototype('array')
				->children()
					->arrayNode('conditions')
						->isRequired()
						->prototype('array')
							->children()
								->scalarNode('type')
									->isRequired()
                                    ->cannotBeEmpty()
                                    ->end()
								->variableNode('configuration')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                    ->end()
							->end()
						->end()
					->end()
					->arrayNode('result')
						->isRequired()
                        ->cannotBeEmpty()
						->children()
							->scalarNode('type')
								->isRequired()
								->end()
							->variableNode('configuration')
                                ->isRequired()
                                ->cannotBeEmpty()
                                ->end()
						->end()
					->end()
				->end()
			->end();
		
        return $treeBuilder;
    }
}