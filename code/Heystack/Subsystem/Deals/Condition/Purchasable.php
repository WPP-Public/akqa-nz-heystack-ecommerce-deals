<?php

namespace Heystack\Subsystem\Deals\Condition;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\PurchasableConditionInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class Purchasable implements PurchasableConditionInterface
{
    const CONDITION_TYPE = 'Purchasable';
    const CONFIGURATION_KEY = 'purchasables';
    const IDENTIFIER_KEY = 'purchasable_identifier';
    const QUANTITY_KEY = 'purchasable_quantity';

    protected $configuration = array();

    /**
     * @var \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    protected $purchasableHolder;
    /**
     * @param PurchasableHolderInterface      $purchasableHolder
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception if the configuration does not have a purchasable identifier
     */
    public function __construct(PurchasableHolderInterface $purchasableHolder, AdaptableConfigurationInterface $configuration)
    {
        if ($configuration->hasConfig(self::CONFIGURATION_KEY) && is_array($purchasables = $configuration->getConfig(self::CONFIGURATION_KEY))) {

            foreach($purchasables as $purchasableConfiguration){

                if(is_array($purchasableConfiguration) && count($purchasableConfiguration)){

                    if(isset($purchasableConfiguration[self::IDENTIFIER_KEY]) && isset($purchasableConfiguration[self::QUANTITY_KEY])){

                        $this->configuration[] = $purchasableConfiguration;

                    }else{

                        throw new \Exception('Purchasable Condition requires that each Purchasable has both an identifier and a quantity');

                    }

                }else{

                    throw new \Exception('Purchasable Condition requires that each Purchasable is configured using an array');

                }

            }


        } else {

            throw new \Exception('Purchasable Condition requires a purchasables configuration array');

        }

        $this->purchasableHolder = $purchasableHolder;

    }
    /**
     * Determines whether this condition has been met based on the configuration of the condition and the state of the purchasable holder.
     *
     * If the $data parameter is present then disregard the contents of the cart and determine the if the condition has been
     * met based on the contents of the data array. Ignore the quantity configuration.
     *
     * @param array $data
     * @return bool
     */
    public function met(array $data = null)
    {

        if (is_array($data) && is_array($data[self::CONFIGURATION_KEY]) && count($data[self::CONFIGURATION_KEY])) {

            foreach($data[self::CONFIGURATION_KEY] as $purchasableConfiguration){

                if(isset($purchasableConfiguration[self::IDENTIFIER_KEY])){

                    if(!$this->matchIdentifierWithConfiguration($purchasableConfiguration[self::IDENTIFIER_KEY])){

                        return false;

                    }

                }else{

                    return false;

                }

            }

            return true;

        }

        foreach($this->configuration as $purchasableConfiguration){

            if(!$this->matchConfigurationWithPurchasableHolder($purchasableConfiguration)){

                return false;

            }

        }

        return true;
    }
    /**
     * @return string
     */
    public function getDescription()
    {

        return 'Must have Purchasable: ' . $this->purchasableIdentifier->getPrimary();

    }
    /**
     * @return Identifier|\Heystack\Subsystem\Core\Identifier\IdentifierInterface
     */
    public function getPurchasableIdentifier()
    {
        return $this->purchasableIdentifier;
    }
    /**
     * Match a purchasable identifier with any in the configuration array
     *
     * @param $purchasableIdentifier
     * @return bool
     */
    protected function matchIdentifierWithConfiguration($purchasableIdentifier)
    {
        foreach($this->configuration as $purchasableConfiguration){

            if($purchasableConfiguration[self::IDENTIFIER_KEY] == $purchasableIdentifier){

                return true;

            }

        }

        return false;
    }
    /**
     * Compare the purchasable configuration with the contents of the purchasable holder and see if there is a match
     *
     * @param $purchasableConfiguration
     * @return bool
     */
    protected function matchConfigurationWithPurchasableHolder($purchasableConfiguration)
    {
        $quantity = 0;

        $purchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier(new Identifier($purchasableConfiguration[self::IDENTIFIER_KEY]));


        if(is_array($purchasables) && count($purchasables)){

            foreach($purchasables as $purchasable){

                if($purchasable instanceof PurchasableInterface){

                    $quantity += $purchasable->getQuantity();

                }

            }

            if($quantity >= $purchasableConfiguration[self::QUANTITY_KEY]){

                return true;

            }

        }

        return false;

    }
}