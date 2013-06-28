<?php
namespace Heystack\Subsystem\Deals\Condition;


use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class Items implements ConditionInterface
{
    const CONDITION_TYPE = 'Items';
    const ITEM_COUNT_KEY = 'item_count';
    const COUNT_BY_PURCHASABLE_QUANTITY_KEY = 'count_by_purchasable';

    protected $itemCount;

    protected $countByPurchasableQuantity;

    /**
     * @var \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    protected $purchasableHolder;


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

            throw new \Exception('Items Condition requires an item count configuration');

        }

        if ($configuration->hasConfig(self::COUNT_BY_PURCHASABLE_QUANTITY_KEY)) {

            $this->countByPurchasableQuantity = $configuration->getConfig(self::COUNT_BY_PURCHASABLE_QUANTITY_KEY);

        } else {

            throw new \Exception('Items Condition requires that it be configured to either count by purchasable quantity or the number of purchasables');

        }

        $this->purchasableHolder = $purchasableHolder;

    }

    /**
     * Return a boolean indicating whether the condition has been met
     *
     * @param  array $data If present this is the data that will be used to determine whether the condition has been met
     * @return mixed
     */
    public function met(array $data = null)
    {
        if (is_array($data) && isset($data[self::ITEM_COUNT_KEY])) {

            if ($data[self::ITEM_COUNT_KEY] >= $this->itemCount) {

                return true;

            }

            return false;

        }

        $purchasables = $this->purchasableHolder->getPurchasables();

        if ($this->countByPurchasableQuantity) {

            $count = 0;

            foreach ($purchasables as $purchasable) {

                $count += $purchasable->getQuantity();

            }

            if ($count >= $this->itemCount) {

                return true;

            }

        } else {

            if (is_array($purchasables) && count($purchasables) >= $this->itemCount) {

                return true;

            }
        }

        return false;
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