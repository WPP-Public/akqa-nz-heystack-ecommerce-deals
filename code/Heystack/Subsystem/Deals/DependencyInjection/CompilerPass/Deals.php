<?php
/**
 * This file is part of the Ecommerce-Deals package
 *
 * @package Ecommerce-Deals
 */

/**
 * CompilerPass namespace
 */
namespace Heystack\Subsystem\Deals\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

use Heystack\Subsystem\Deals\DependencyInjection\ContainerExtension as DealsContainerExtension;

use Heystack\Subsystem\Ecommerce\Services as EcommerceServices;
use Heystack\Subsystem\Core\Services as CoreServices;

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

        $dealsExtension = $container->getExtension('deals');
        
        if($dealsExtension instanceof DealsContainerExtension){
            
            $config = $dealsExtension->getConfig();
        
            //where you come across an instance of time condition (check example_extension_invocation)
            //you should create DecoratorDefintion using the "condition.prototype_time" service

            if ($container->hasDefinition(EcommerceServices::TRANSACTION) && $config) {

                $transactionDefintion = $container->getDefinition(EcommerceServices::TRANSACTION);

                foreach ($config as $dealId => $deal) {
                    
                    $dealDefintionID = "deals.deal.$dealId";
                    $dealDefinition = new DefinitionDecorator('deals.deal_handler');
                    $dealDefinition->addArgument($dealId);
                    
                    //Add all conditions
                    foreach ($deal['conditions'] as $conditionId => $condition) {
                        
                        //Create the configuration for the condition
                        $conditionConfigurationDefinition = new DefinitionDecorator('deals.condition.configuration');
                        $conditionConfigurationDefinition->addArgument($condition['configuration']);
                        
                        $conditionConfigurationID = "deals.deal.$dealId.condition_$conditionId.configuration";
                        
                        $container->setDefinition($conditionConfigurationID, $conditionConfigurationDefinition);
                        
                        //Create the condition and pass the configuration to the constructor
                        $conditionDefinition = new DefinitionDecorator("deals.condition." . strtolower($condition['type']));
                        $conditionDefinition->addArgument(new Reference($conditionConfigurationID));
                        
                        $conditionDefinitionID = "deals.deal.$dealId.condition_$conditionId";
                        
                        $container->setDefinition($conditionDefinitionID, $conditionDefinition);

                        //Add the condition to the deal
                        $dealDefinition->addMethodCall(
                                'addCondition',
                                array(
                                    new Reference($conditionDefinitionID)
                                )
                        );
                    }
                    
                    //Add the result processor
                    if(isset($deal['result']) && isset($deal['result']['configuration']) && isset($deal['result']['type'])){
                        
                        $resultDefinition = new DefinitionDecorator('deals.result.' . strtolower($deal['result']['type']));
                        
                        
                        $resultConfigurationDefinition = new DefinitionDecorator('deals.result.configuration');
                        $resultConfigurationDefinition->addArgument($deal['result']['configuration']);
                        $resultConfigurationID = "deals.deal.$dealId.result.configuration";
                        
                        $container->setDefinition($resultConfigurationID, $resultConfigurationDefinition);
                        
                        $resultDefinition->addArgument(new Reference($resultConfigurationID));
                        $resultDefinition->addMethodCall('setDealHandler', array(new Reference($dealDefintionID)));
                        
                        //Set the result definition on the container
                        $resultID = "deals.deal.$dealId.result";
                        $container->setDefinition($resultID, $resultDefinition);
                        
                        //Set the result on the deal
                        $dealDefinition->addMethodCall(
                                'setResult',
                                array(
                                    new Reference($resultID)
                                )
                        );
                        
                    }
                    
                    //Put the deal in the container
                    $container->setDefinition($dealDefintionID, $dealDefinition);
                    
                    
                    //Create the deal subscriber and add it to the event dispatcher
                    $subscriberDefinition = new DefinitionDecorator('deals.subscriber');
                    $subscriberDefinition->addArgument(new Reference($dealDefintionID));
                    
                    $subscriberDefinitionID = $dealDefintionID . '.subscriber';
                    
                    $container->setDefinition($subscriberDefinitionID, $subscriberDefinition);
                    
                    $eventDispatcherDefinition = $container->getDefinition(CoreServices::EVENT_DISPATCHER);
                    
                    $eventDispatcherDefinition->addMethodCall(
                            'addSubscriber',
                            array(new Reference($subscriberDefinitionID))
                    );
                                        
                    //add each deal service (Deinfition) to the transation
                    $transactionDefintion->addMethodCall(
                        'addModifier',
                        array(
                            new Reference($dealDefintionID)
                        )
                    );
                }

            }
        
        }

    }
    
}