<?php

namespace Heystack\Subsystem\Deals\Interfaces;

use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface ResultInterface
{
    /**
     * Returns a short string that describes what the result does
     */
    public function description();

    /**
     * Main function that determines what the result does
     */
    public function process();

    /**
     * Set the deal handler on the result
     *
     * @param Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface $dealHandler
     */
    public function setDealHandler(DealHandlerInterface $dealHandler);

}
