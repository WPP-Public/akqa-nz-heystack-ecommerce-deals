<?php

namespace Heystack\Subsystem\Deals\Traits;

use Heystack\Subsystem\Core\Identifier\IdentifierInterface;

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

    /**
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function setFreeQuantity(IdentifierInterface $dealIdentifier, $quantity)
    {
        $this->freeQuantities[$dealIdentifier->getFull()] = $quantity;
    }

    /**
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function addFreeQuantity(IdentifierInterface $dealIdentifier, $quantity = 1)
    {
        $this->freeQuantities[$dealIdentifier->getFull()] += $quantity;
    }

    /**
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function subtractFreeQuantity(IdentifierInterface $dealIdentifier, $quantity = 1)
    {
        $this->freeQuantities[$dealIdentifier->getFull()] -= $quantity;
    }

    /**
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
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
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
     * @return int
     */
    public function getFreeQuantity(IdentifierInterface $dealIdentifier = null)
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