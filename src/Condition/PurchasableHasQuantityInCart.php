<?php

namespace Heystack\Deals\Condition;

use Heystack\Core\Interfaces\HasEventServiceInterface;
use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Deals\Interfaces\ConditionAlmostMetInterface;
use Heystack\Deals\Interfaces\ConditionInterface;
use Heystack\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Deals\Interfaces\NonPurchasableInterface;
use Heystack\Deals\Traits\HasDealHandlerTrait;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Purchasable\PurchasableHolder\Interfaces\HasPurchasableHolderInterface;
use Heystack\Purchasable\PurchasableHolder\Traits\HasPurchasableHolderTrait;

/**
 *
 * @copyright  Heyday
 * @author Stevie Mayhew <stevie@heyday.co.nz>
 * @package \Heystack\Deals\Condition
 */
class PurchasableHasQuantityInCart
    implements
        ConditionInterface,
        ConditionAlmostMetInterface,
        HasDealHandlerInterface,
        HasPurchasableHolderInterface
{
    use HasDealHandlerTrait;
    use HasPurchasableHolderTrait;

    const CONDITION_TYPE = 'PurchasableHasQuantityInCart';
    const PURCHASABLE_IDENTIFIERS = 'purchasables_identifiers';
    const MINIMUM_QUANTITY_KEY = 'minimum_quantity';

    protected $purchasableIdentifiers = [];
    protected $minimumQuantity;
    protected $configuration;

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

                $this->purchasableIdentifiers[] = $purchasableIdentifier;

            }


        } else {

            throw new \Exception('Quantity Of Purchasables In Cart Condition requires an array of purchasable identifiers');

        }

        if ($configuration->hasConfig(self::MINIMUM_QUANTITY_KEY)) {

            $this->minimumQuantity = $configuration->getConfig(self::MINIMUM_QUANTITY_KEY);

        } else {

            throw new \Exception(
                'Quantity Of Purchasables In Cart Condition requires a minimum quantity per item to be configured'
            );

        }

        $this->configuration = $configuration;


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
        $met = false;
        $purchasables = [];

        foreach ($this->purchasableIdentifiers as $purchasableIdentifier) {

            $items = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier(
                $purchasableIdentifier
            );

            if (is_array($items)) {
                $purchasables[] = $items;
            }

        }

        $purchasables = call_user_func_array('array_merge', $purchasables);


        foreach ($purchasables as $purchasable) {

            if ($purchasable->getQuantity() >= $this->minimumQuantity) {
                $met = true;
                break;
            }


        }

        return $met;
    }

    public function almostMet()
    {
        $purchasableHolder = $this->getPurchasableHolder();
        $met = false;
        $purchasables = [];

        if ($purchasableHolder instanceof HasEventServiceInterface) {
            $this->purchasableHolder->getEventService()->setEnabled(false);
        }

        foreach ($this->purchasableIdentifiers as $purchasableIdentifier) {

            $items = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier(
                $purchasableIdentifier
            );

            if (is_array($items)) {
                $purchasables[] = $items;
            }

        }

        if ($purchasables) {
            $purchasables = count($purchasables) > 1 ? call_user_func_array('array_merge', $purchasables) : reset($purchasables);
        }

        foreach ($purchasables as $purchasable) {

            // It is not relevant to test adding a non purchasable item to the cart,
            // because the user can never actually add it
            if (!$purchasable instanceof NonPurchasableInterface) {

                $quantity = $purchasable->getQuantity();
                $this->purchasableHolder->setPurchasable($purchasable, $quantity + 1);
                $met = $this->met();
                $this->purchasableHolder->setPurchasable($purchasable, $quantity);


            }

        }

        if ($purchasableHolder instanceof HasEventServiceInterface) {
            $this->purchasableHolder->getEventService()->setEnabled(true);
        }

        return $met;
    }

    /**
     * @return string
     */
    public function getDescription()
    {

        return 'One of the items must have at least ' . $this->minimumQuantity . ' quantity in the cart. Items include ' . implode(
            ',',
            $this->purchasableIdentifiers
        );

    }

    /**
     * @param mixed $minimumQuantity
     */
    public function setMinimumQuantity($minimumQuantity)
    {
        $this->minimumQuantity = $minimumQuantity;
    }

    /**
     * @return mixed
     */
    public function getMinimumQuantity()
    {
        return $this->minimumQuantity;
    }

    /**
     * @param array $purchasableIdentifiers
     */
    public function setPurchasableIdentifiers($purchasableIdentifiers)
    {
        $this->purchasableIdentifiers = $purchasableIdentifiers;
    }

    /**
     * @return array
     */
    public function getPurchasableIdentifiers()
    {
        return $this->purchasableIdentifiers;
    }
}