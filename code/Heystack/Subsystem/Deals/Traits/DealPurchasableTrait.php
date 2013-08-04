<?php

namespace Heystack\Subsystem\Deals\Traits;

use Heystack\Subsystem\Core\Identifier\Identifier;

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

    protected $freeQuantities = array();

    /**
     * @param \Heystack\Subsystem\Core\Identifier\Identifier $dealIdentifier
     * @param int $quantity
     */
    public function setFreeQuantity(Identifier $dealIdentifier, $quantity)
    {
        $this->freeQuantities[$dealIdentifier->getFull()] = $quantity;
    }

    /**
     * @param \Heystack\Subsystem\Core\Identifier\Identifier $dealIdentifier
     * @param int $quantity
     */
    public function addFreeQuantity(Identifier $dealIdentifier, $quantity = 1)
    {
        $this->freeQuantities[$dealIdentifier->getFull()] += $quantity;
    }

    /**
     * @param \Heystack\Subsystem\Core\Identifier\Identifier $dealIdentifier
     * @param int $quantity
     */
    public function subtractFreeQuantity(Identifier $dealIdentifier, $quantity = 1)
    {
        $this->freeQuantities[$dealIdentifier->getFull()] -= $quantity;
    }

    /**
     * @param \Heystack\Subsystem\Core\Identifier\Identifier $dealIdentifier
     * @return bool
     */
    public function hasFreeItems(Identifier $dealIdentifier = null)
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
     * @param \Heystack\Subsystem\Core\Identifier\Identifier $dealIdentifier
     * @return int
     */
    public function getFreeQuantity(Identifier $dealIdentifier = null)
    {
        if (!is_null($dealIdentifier) && isset($this->freeQuantities[$dealIdentifier->getFull()])) {

            return $this->freeQuantities[$dealIdentifier->getFull()];

        }

        $total = 0;

        foreach ($this->freeQuantities as $quantity) {

            $total += $quantity;

        }

        return $total;
    }

    public function getFreeQuantities()
    {
        return $this->freeQuantities;
    }
}