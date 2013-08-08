<?php

namespace Heystack\Subsystem\Deals\Condition;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\Interfaces\HasEventServiceInterface;
use Heystack\Subsystem\Core\Traits\HasEventService;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionAlmostMetInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;
use Heystack\Subsystem\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Subsystem\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\HasPurchasableHolderInterface;
use Heystack\Subsystem\Deals\Interfaces\NonPurchasableInterface;
use Heystack\Subsystem\Deals\Result\FreeGift;
use Heystack\Subsystem\Deals\Traits\HasDealHandler;
use Heystack\Subsystem\Deals\Traits\HasPurchasableHolder;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Subsystem\Products\ProductHolder\ProductHolder;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package \Heystack\Subsystem\Deals\Condition
 */
class QuantityOfPurchasablesInCart implements ConditionInterface, ConditionAlmostMetInterface, HasDealHandlerInterface, HasPurchasableHolderInterface
{
    use HasDealHandler;
    use HasPurchasableHolder;

    const CONDITION_TYPE = 'QuantityOfPurchasablesInCart';
    const PURCHASABLE_IDENTIFIERS = 'purchasables_identifiers';
    const MINIMUM_QUANTITY_KEY = 'minimum_quantity';

    protected $purchasableIdentifiers = array();
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
        $quantity = 0;

        $purchasables = array();

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

            if ($purchasable instanceof DealPurchasableInterface) {

                $quantity += $purchasable->getQuantity();

                // TODO: Refactor this coupling
                if ($this->dealHandler->getResult() instanceof FreeGift) {

                    $quantity -= $purchasable->getFreeQuantity();

                }

            }


        }

        return (int) floor($quantity / $this->minimumQuantity);
    }

    /**
     * This method will iterate of the current product holder, increasing each item by one (then decrementing)
     * After incrementing each item it checks if the the condition is now satisfied.
     *
     * The setEnabled calls are to make sure that when adding and removing from the purchasable holder
     * we don't fire the events that would cause other parts of the system to change
     * also, this means infinite recursion doesn't occur
     *
     * @return bool
     */
    public function almostMet()
    {
        $currentCount = $this->met();
        $purchasableHolder = $this->getPurchasableHolder();
        $met = false;

        if ($purchasableHolder instanceof HasEventServiceInterface) {
            $this->purchasableHolder->getEventService()->setEnabled(false);
        }

        foreach ($this->purchasableHolder->getPurchasables() as $purchasable) {

            // It is not relevant to test adding a non purchasable item to the cart,
            // because the user can never actually add it
            if (!$purchasable instanceof NonPurchasableInterface) {
                $quantity = $purchasable->getQuantity();
                $this->purchasableHolder->setPurchasable($purchasable, $quantity + 1);
                $metCount = $this->met();
                $this->purchasableHolder->setPurchasable($purchasable, $quantity);
                if ($metCount > $currentCount) {
                    $met = true;
                    break;
                }
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

        return 'Must have at least ' . $this->minimumQuantity . ' of any of the ff: ' . implode(
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