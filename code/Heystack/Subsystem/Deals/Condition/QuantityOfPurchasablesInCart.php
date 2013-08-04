<?php

namespace Heystack\Subsystem\Deals\Condition;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;
use Heystack\Subsystem\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\HasPurchasableHolderInterface;
use Heystack\Subsystem\Deals\Traits\HasDealHandler;
use Heystack\Subsystem\Deals\Interfaces\QuantityOfPurchasablesInCartInterface;
use Heystack\Subsystem\Deals\Result\FreeGift;
use Heystack\Subsystem\Deals\Traits\HasPurchasableHolder;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class QuantityOfPurchasablesInCart implements ConditionInterface, HasDealHandlerInterface, HasPurchasableHolderInterface
{
    use HasDealHandler;
    use HasPurchasableHolder;

    const CONDITION_TYPE = 'QuantityOfPurchasablesInCart';
    const PURCHASABLE_IDENTIFIERS = 'purchasables_identifiers';
    const MINIMUM_QUANTITY_KEY = 'minimum_quantity';

    protected $purchasableIdentifiers = array();
    protected $minimumQuantity;

    /**
     * @param PurchasableHolderInterface $purchasableHolder
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception if the configuration does not have a purchasable identifier
     */
    public function __construct(
        PurchasableHolderInterface $purchasableHolder,
        AdaptableConfigurationInterface $configuration
    ) {
        if ($configuration->hasConfig(self::PURCHASABLE_IDENTIFIERS) && is_array(
                $purchasableIdentifiers = $configuration->getConfig(self::PURCHASABLE_IDENTIFIERS)
            )
        ) {

            foreach ($purchasableIdentifiers as $purchasableIdentifier) {

                $this->purchasableIdentifiers[] = new Identifier($purchasableIdentifier);

            }


        } else {

            throw new \Exception('Quantity Of Purchasables In Cart Condition requires an array of purchasable identifiers');

        }

        if ($configuration->hasConfig(self::MINIMUM_QUANTITY_KEY)) {

            $this->minimumQuantity = $configuration->getConfig(self::MINIMUM_QUANTITY_KEY);

        } else {

            throw new \Exception('Quantity Of Purchasables In Cart Condition requires a minimum quantity to be configured');

        }


        $this->purchasableHolder = $purchasableHolder;

    }

    /**
     * @return string that indicates the type of condition this class is implementing
     */
    public function getType()
    {
        return self::CONDITION_TYPE;
    }

    /**
     * Determines whether this condition has been met based on the configuration of the condition and the state of the purchasable holder.
     *
     * If the $data parameter is present then disregard the contents of the cart and determine the if the condition has been
     * met based on the contents of the data array.
     *
     * @return bool
     */
    public function met()
    {
        $quantity = 0;

        $purchasables = array();

        foreach ($this->purchasableIdentifiers as $purchasableIdentifier) {

            $retrievedPurchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier(
                $purchasableIdentifier
            );

            if (is_array($retrievedPurchasables) && count($retrievedPurchasables)) {

                $purchasables = array_merge(
                    $purchasables,
                    $retrievedPurchasables
                );
            }
        }


        foreach ($purchasables as $purchasable) {

            $quantity += $purchasable->getQuantity();

            // TODO: Refactor this coupling
            if ($this->dealHandler->getResult() instanceof FreeGift) {

                $quantity -= $purchasable->getFreeQuantity();

            }

        }

        return (int) floor($quantity / $this->minimumQuantity);
    }

    /**
     * @return string
     */
    public function getDescription()
    {

        return 'Must have at least ' . $this->minimumQuantity . ' of any of the ff: ' . implode(
            ',',
            $this->purchasableIdentifiers
        );

    }

}