<?php

namespace Heystack\Subsystem\Deals\Interfaces;

/**
 *
 * @copyright  Heyday
 * @author Stevie Mayhew <stevie@heyday.co.nz>
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