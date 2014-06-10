<?php

namespace Heystack\Deals\Events;

use Heystack\Deals\Interfaces\DealHandlerInterface;
use Heystack\Deals\Traits\HasDealHandler;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author  Stevie Mayhew <stevie@heyday.co.nz>
 * @package Heystack\Deals
 */
class ConditionEvent extends Event
{
    use HasDealHandler;

    /**
     * @param DealHandlerInterface $dealHandler
     */
    public function __construct(DealHandlerInterface $dealHandler)
    {
        $this->dealHandler = $dealHandler;
    }
}
