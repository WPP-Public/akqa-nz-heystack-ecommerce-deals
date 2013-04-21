<?php

namespace Heystack\Subsystem\Deals\Interfaces;

use Heystack\Subsystem\Ecommerce\Transaction\Interfaces\TransactionModifierInterface;


/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface DealHandlerInterface extends TransactionModifierInterface
{
    
    public function setResult($result);

    public function addCondition($condition);
    
    public function updateTotal();

    public function getPromotionalMessage();

}
