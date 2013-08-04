<?php

namespace Heystack\Subsystem\Deals\Interfaces;

/**
 * Class HasDealHandlerInterface
 * @package Heystack\Subsystem\Deals\Interfaces
 */
interface HasDealHandlerInterface
{
    /**
     * @return DealHandlerInterface
     */
    public function getDealHandler();

    /**
     * @param DealHandlerInterface $deal
     * @return mixed
     */
    public function setDealHandler(DealHandlerInterface $deal);
}