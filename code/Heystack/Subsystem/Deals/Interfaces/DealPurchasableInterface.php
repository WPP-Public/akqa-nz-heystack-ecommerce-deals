<?php

namespace Heystack\Subsystem\Deals\Interfaces;

use Heystack\Subsystem\Core\Identifier\Identifier;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface DealPurchasableInterface
{
    /**
     * @param Identfier $dealIdentifier
     * @param int $quantity
     */
    public function setFreeQuantity(Identifier $dealIdentifier, $quantity);

    /**
     * @param Identifier $dealIdentifier
     * @param int $quantity
     */
    public function addFreeQuantity(Identifier $dealIdentifier, $quantity = 1);

    /**
     * @param Identifier $dealIdentfier
     * @param int $quantity
     */
    public function subtractFreeQuantity(Identifier $dealIdentifier, $quantity = 1);

    /**
     * @param Identifier $dealIdentifier
     * @return bool
     */
    public function hasFreeItems(Identifier $dealIdentifier = null);

    /**
     * @param Identifier $dealIdentifier
     * @return int
     */
    public function getFreeQuantity(Identifier $dealIdentifier = null);
}