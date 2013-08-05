<?php
namespace Heystack\Subsystem\Deals\Condition;


use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionAlmostMetInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;
use Heystack\Subsystem\Deals\Interfaces\HasPurchasableHolderInterface;
use Heystack\Subsystem\Deals\Traits\HasPurchasableHolder;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class ItemsInCart implements ConditionInterface, ConditionAlmostMetInterface, HasPurchasableHolderInterface
{
    use HasPurchasableHolder;

    const CONDITION_TYPE = 'ItemsInCart';
    const ITEM_COUNT_KEY = 'item_count';
    const COUNT_BY_PURCHASABLE_QUANTITY_KEY = 'count_by_purchasable';

    protected $itemCount;

    protected $countByPurchasableQuantity;

    /**
     * @param PurchasableHolderInterface $purchasableHolder
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception when there is no configuration identifier or is not configured fully
     */
    public function __construct(
        PurchasableHolderInterface $purchasableHolder,
        AdaptableConfigurationInterface $configuration
    ) {
        if ($configuration->hasConfig(self::ITEM_COUNT_KEY)) {

            $this->itemCount = $configuration->getConfig(self::ITEM_COUNT_KEY);

        } else {

            throw new \Exception('Items In Cart Condition requires an item count configuration');

        }

        if ($configuration->hasConfig(self::COUNT_BY_PURCHASABLE_QUANTITY_KEY)) {

            $this->countByPurchasableQuantity = $configuration->getConfig(self::COUNT_BY_PURCHASABLE_QUANTITY_KEY);

        } else {

            throw new \Exception('Items In Cart Condition requires that it be configured to either count by purchasable quantity or the number of purchasables');

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
     * Return a boolean indicating whether the condition has been met
     *
     * @return int
     */
    public function met()
    {
        $count = 0;

        $purchasables = $this->purchasableHolder->getPurchasables();

        if ($this->countByPurchasableQuantity) {

            foreach ($purchasables as $purchasable) {

                $count += $purchasable->getQuantity();

            }

        } elseif (is_array($purchasables)) {

            $count = count($purchasables);

        }

        return $count >= $this->itemCount;
    }

    /**
     * Check if the condition is almost met
     *
     * Almost met is when one more action completed by the user to the cart will promote this deal to being completed.
     * When a condition will complete regardless of user action, return $this->met()
     *
     * @see Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface
     * @return boolean
     */
    public function almostMet()
    {
        $count = 0;

        $purchasables = $this->purchasableHolder->getPurchasables();

        if ($this->countByPurchasableQuantity) {

            foreach ($purchasables as $purchasable) {

                $count += $purchasable->getQuantity();

            }

        } elseif (is_array($purchasables)) {

            $count = count($purchasables);

        }

        return $count + 1 >= $this->itemCount;
    }

    /**
     * Returns a short string that describes what the condition does
     */
    public function getDescription()
    {
        if ($this->countByPurchasableQuantity) {

            return 'Must have a total of ' . $this->itemCount . ' products ( by quantity) in the cart';

        }

        return 'Must have a total of ' . $this->itemCount . ' individual products in the cart';
    }

}