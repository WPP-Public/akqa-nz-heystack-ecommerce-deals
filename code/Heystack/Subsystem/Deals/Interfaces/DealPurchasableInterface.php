<?php

namespace Heystack\Subsystem\Deals\Interfaces;

use Heystack\Subsystem\Core\Identifier\IdentifierInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface DealPurchasableInterface extends PurchasableInterface
{
    /**
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function setFreeQuantity(IdentifierInterface $dealIdentifier, $quantity);

    /**
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function addFreeQuantity(IdentifierInterface $dealIdentifier, $quantity = 1);

    /**
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function subtractFreeQuantity(IdentifierInterface $dealIdentifier, $quantity = 1);

    /**
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
     * @return bool
     */
    public function hasFreeItems(IdentifierInterface $dealIdentifier = null);

    /**
     * @param \Heystack\Subsystem\Core\Identifier\IdentifierInterface $dealIdentifier
     * @return int
     */
    public function getFreeQuantity(IdentifierInterface $dealIdentifier = null);

    /**
     * @param IdentifierInterface $dealIdentifier
     * @param $discountAmount
     * @return mixed
     */
    public function setDealDiscount(IdentifierInterface $dealIdentifier, $discountAmount);

    /**
     * @param IdentifierInterface $dealIdentifier
     * @return float
     */
    public function getDealDiscount(IdentifierInterface $dealIdentifier = null);
}