<?php
/**
 * This file is part of the Ecommerce-Deals package
 *
 * @package Ecommerce-Deals
 */

/**
 * CompilerPass namespace
 */
namespace Heystack\Deals\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Merges extensions definition calls into the container builder.
 *
 * When there exists an extension which defines calls on an existing service,
 * this compiler pass will merge those calls without overwriting.
 *
 * @copyright  Heyday
 * @author Glenn Bautista
 * @package Heystack
 */
class Deals implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $dealsSubscriberDefinition = $container->getDefinition('deals_subscriber');

        foreach ($container->findTaggedServiceIds('deals.deal') as $id => $_) {
            $dealsSubscriberDefinition->addMethodCall('addDealHandler', [new Reference($id)]);
        }
    }
}
