<?php

namespace Heystack\Deals\Interfaces;

/**
 *
 * @copyright  Heyday
 * @author Stevie Mayhew <stevie@heyday.co.nz>
 * @package Heystack\Deals\Interfaces
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