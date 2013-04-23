<?php

namespace Heystack\Subsystem\Deals\Interfaces;

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
    public function getDescription();
    /**
     * Main function that determines what the result does
     */
    public function process(DealHandlerInterface $dealHandler);
}
