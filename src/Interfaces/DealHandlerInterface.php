<?php

namespace Heystack\Deals\Interfaces;

use Heystack\Core\Storage\Interfaces\ParentReferenceInterface;
use Heystack\Core\Storage\StorableInterface;
use Heystack\Ecommerce\Transaction\Interfaces\TransactionModifierInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface DealHandlerInterface extends
    TransactionModifierInterface,
    ParentReferenceInterface,
    StorableInterface,
    HasPriorityInterface
{
    /**
     * @param ResultInterface $result
     * @return void
     */
    public function setResult(ResultInterface $result);

    /**
     * @param \Heystack\Deals\Interfaces\ConditionInterface $condition
     * @return void
     */
    public function addCondition(ConditionInterface $condition);

    /**
     * @return void
     */
    public function updateTotal();

    /**
     * @param $type
     * @return mixed
     */
    public function getPromotionalMessage($type);

    /**
     * @return \Heystack\Deals\Interfaces\ConditionInterface[]
     */
    public function getConditions();

    /**
     * Returns the number of times that each condition was met more than once
     * @return int
     */
    public function getConditionsMetCount();

    /**
     * @return \Heystack\Deals\Interfaces\ResultInterface
     */
    public function getResult();

    /**
     * Returns whether the deal is almost completed based on the conditions it has
     * @return bool
     */
    public function almostMet();

    /**
     * @param \Heystack\Ecommerce\Purchasable\Interfaces\PurchasableInterface[] $purchasbles
     * @return \SebastianBergmann\Money\Money
     */
    public function getPurchasablesTotalWithDiscounts(array $purchasbles);

    /**
     * @param \Heystack\Deals\Interfaces\HasPriorityInterface $other
     * @return int
     */
    public function compareTo(HasPriorityInterface $other);
}
