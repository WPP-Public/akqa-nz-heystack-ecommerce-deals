<?php
/**
 * This file is part of the Ecommerce-Deals package
 *
 * @package Ecommerce-Deals
 */

/**
 * Dependency Injection namespace
 */
namespace Heystack\Subsystem\Deals\DependencyInjection;

use Heystack\Subsystem\Core\Loader\DBClosureLoader;
use Heystack\Subsystem\Core\Services as CoreServices;
use Heystack\Subsystem\Deals\Config\ContainerConfig;
use Heystack\Subsystem\Deals\Interfaces\DealDataInterface;
use Heystack\Subsystem\Ecommerce\Services as EcommerceServices;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Container extension for Heystack.
 *
 * If Heystacks services are loaded as an extension (this happens when there is
 * a primary services.yml file in mysite/config) then this is the container
 * extension that loads heystacks services.yml
 *
 * @copyright  Heyday
 * @author Cam Spiers <cameron@heyday.co.nz>
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class ContainerExtension extends Extension
{
    /**
     * Loads a services.yml file into a fresh container, ready to me merged
     * back into the main container
     *
     * @param  array            $configs
     * @param  ContainerBuilder $container
     * @return null
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        (new YamlFileLoader(
            $container,
            new FileLocator(ECOMMERCE_DEALS_BASE_PATH . '/config')
        ))->load('services.yml');

        $config = (new Processor())->processConfiguration(
            new ContainerConfig(),
            $configs
        );

        if (isset($config['deals_db'])) {
            $dealsDbConfig = array(
                'deals' => array()
            );
            
            $handler = function (DealDataInterface $record) use (&$dealsDbConfig) {
                $configArray = $record->getConfigArray();

                if (is_array($configArray)) {

                    $dealsDbConfig['deals'][$record->getName()] = $configArray;

                }
            };
            
            $resource = call_user_func([$config['deals_db']['from'], 'get'])->where($config['deals_db']['where']);
            
            (new DBClosureLoader($handler))->load($resource);
            
            $configs[] = $dealsDbConfig;
        }

        $config = (new Processor())->processConfiguration(
            new ContainerConfig(),
            $configs
        );

        foreach ($config['deals'] as $dealId => $deal) {
            $this->addDeal($container, $dealId, $deal);
        }

    }
    /**
     * @param  ContainerBuilder $container
     * @param                   $dealId
     * @param                   $deal
     * @return mixed
     */
    protected function addDeal(ContainerBuilder $container, $dealId, $deal)
    {
        $dealDefinitionID = "deals.deal.$dealId";
        $dealDefinition = $this->getDealDefinition($dealId, $deal['promotional_message']);

        //Add all conditions
        foreach ($deal['conditions'] as $conditionId => $condition) {
            $this->addCondition($container, $dealId, $condition, $conditionId, $dealDefinition);
        }

        //Add the result processor
        if (isset($deal['result']) && isset($deal['result']['configuration']) && isset($deal['result']['type'])) {
            $this->addResult($container, $dealId, $deal, $dealDefinition);
        }

        //Create the deal subscriber and add it to the event dispatcher
        $this->addSubscriber($container, $dealDefinitionID);

        //Put the deal in the container
        $container->setDefinition($dealDefinitionID, $dealDefinition);

        return $deal;
    }
    /**
     * @param $dealId
     * @param $promotionalMessage
     * @return DefinitionDecorator
     * @return \Symfony\Component\DependencyInjection\DefinitionDecorator
     */
    protected function getDealDefinition($dealId, $promotionalMessage)
    {
        $dealDefinition = new DefinitionDecorator('deals.deal_handler');
        $dealDefinition->addArgument($dealId);
        $dealDefinition->addArgument($promotionalMessage);
        $dealDefinition->addTag(EcommerceServices::TRANSACTION . '.modifier');
        $dealDefinition->addTag(CoreServices::SS_ORM_BACKEND . '.data_provider');

        return $dealDefinition;
    }
    /**
     * @param  ContainerBuilder $container
     * @param                   $dealDefintionID
     * @return void
     */
    protected function addSubscriber(ContainerBuilder $container, $dealDefintionID)
    {
        $subscriberDefinition = new DefinitionDecorator('deals.subscriber');
        $subscriberDefinition->addArgument(new Reference($dealDefintionID));
        $subscriberDefinition->addTag(CoreServices::EVENT_DISPATCHER . '.subscriber');
        $container->setDefinition($dealDefintionID . '.subscriber', $subscriberDefinition);
    }

    /**
     * @param ContainerBuilder $container
     * @param $dealId
     * @param $deal
     * @param $dealDefinition
     */
    protected function addResult(ContainerBuilder $container, $dealId, $deal, $dealDefinition)
    {
        $resultConfigurationID = $this->addResultConfiguration($container, $dealId, $deal);

        $resultDefinition = new DefinitionDecorator('deals.result.' . strtolower($deal['result']['type']));
        $resultDefinition->addArgument(new Reference($resultConfigurationID));
        $resultDefinition->addTag(CoreServices::EVENT_DISPATCHER . '.subscriber');

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
    /**
     * @param  ContainerBuilder $container
     * @param                   $dealId
     * @param                   $deal
     * @return string
     */
    protected function addResultConfiguration(ContainerBuilder $container, $dealId, $deal)
    {
        $resultConfigurationDefinition = new DefinitionDecorator('deals.result.configuration');
        $resultConfigurationDefinition->addArgument($deal['result']['configuration']);
        $resultConfigurationID = "deals.deal.$dealId.result.configuration";
        $container->setDefinition($resultConfigurationID, $resultConfigurationDefinition);

        return $resultConfigurationID;
    }
    /**
     * @param ContainerBuilder $container
     * @param                  $dealId
     * @param                  $condition
     * @param                  $conditionId
     * @param                  $dealDefinition
     */
    protected function addCondition(ContainerBuilder $container, $dealId, $condition, $conditionId, $dealDefinition)
    {
        //Create the configuration for the condition
        $conditionConfigurationID = $this->addConditionConfiguration($container, $dealId, $condition, $conditionId);

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
    /**
     * @param  ContainerBuilder $container
     * @param                   $dealId
     * @param                   $condition
     * @param                   $conditionId
     * @return string
     */
    protected function addConditionConfiguration(ContainerBuilder $container, $dealId, $condition, $conditionId)
    {
        $conditionConfigurationDefinition = new DefinitionDecorator('deals.condition.configuration');
        $conditionConfigurationDefinition->addArgument($condition['configuration']);
        $conditionConfigurationID = "deals.deal.$dealId.condition_$conditionId.configuration";
        $container->setDefinition($conditionConfigurationID, $conditionConfigurationDefinition);

        return $conditionConfigurationID;
    }

    /**
     * Returns the namespace of the container extension
     * @return string
     */
    public function getNamespace()
    {
        return 'deals';
    }

    /**
     * Returns Xsd Validation Base Path, which is not used, so false
     * @return boolean
     */
    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * Returns the container extensions alias
     * @return string
     */
    public function getAlias()
    {
        return 'deals';
    }
}
