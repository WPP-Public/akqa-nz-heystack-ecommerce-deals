<?php

namespace Heystack\Deals\Traits;

use Heystack\Core\Identifier\IdentifierInterface;
use SebastianBergmann\Money\Money;

/**
 * This trait implements the functionality defined in the DealPurchasableInterface.
 *
 * Please note that you still have to include the $freeQuantity property on the ExtraData array for this information to be saved on state.
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
trait DealPurchasableTrait
{

    protected $freeQuantities = [];
    protected $dealDiscounts = [];

    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function setFreeQuantity(IdentifierInterface $dealIdentifier, $quantity)
    {
        $this->freeQuantities[$dealIdentifier->getFull()] = $quantity;
    }

    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function addFreeQuantity(IdentifierInterface $dealIdentifier, $quantity = 1)
    {
        $this->freeQuantities[$dealIdentifier->getFull()] += $quantity;
    }

    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function subtractFreeQuantity(IdentifierInterface $dealIdentifier, $quantity = 1)
    {
        $this->freeQuantities[$dealIdentifier->getFull()] -= $quantity;
    }

    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @return bool
     */
    public function hasFreeItems(IdentifierInterface $dealIdentifier = null)
    {
        if (is_null($dealIdentifier)) {

            foreach ($this->freeQuantities as $quantity) {

                if ($quantity) {

                    return true;

                }

            }

            return false;
        }

        return (isset($this->freeQuantities[$dealIdentifier->getFull(
            )]) && $this->freeQuantities[$dealIdentifier->getFull()] > 0);
    }

    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @return int
     */
    public function getFreeQuantity(IdentifierInterface $dealIdentifier = null)
    {
        if (!is_null($dealIdentifier) && isset($this->freeQuantities[$dealIdentifier->getFull()])) {

            return $this->freeQuantities[$dealIdentifier->getFull()];

        }

        return array_sum($this->freeQuantities);
    }

    public function getFreeQuantities()
    {
        return $this->freeQuantities;
    }

    /**
     * @param IdentifierInterface $dealIdentifier
     * @param $discountAmount
     */
    public function setDealDiscount(IdentifierInterface $dealIdentifier,Money $discountAmount)
    {
        $this->dealDiscounts[$dealIdentifier->getFull()] = $discountAmount;
    }

    /**
     * @param IdentifierInterface $dealIdentifier
     * @return float
     */
    public function getDealDiscount(IdentifierInterface $dealIdentifier = null)
    {
        if (!is_null($dealIdentifier)) {

            if (isset($this->dealDiscounts[$dealIdentifier->getFull()])) {

                return $this->dealDiscounts[$dealIdentifier->getFull()];

            }

            return $this->getCurrencyService()->getZeroMoney();

        }

        $total = $this->getCurrencyService()->getZeroMoney();

        foreach ( $this->dealDiscounts as $dealDiscount ) {

            if ($dealDiscount instanceof Money) {
                $total = $total->add($dealDiscount);
            }

        }

        return $total;
    }

    /**
     * @return \Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface
     */
    abstract function getCurrencyService();
}
