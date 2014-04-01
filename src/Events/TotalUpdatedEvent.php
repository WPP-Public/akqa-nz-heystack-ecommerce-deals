<?php

namespace Heystack\Deals\Events;

use Heystack\Deals\Traits\HasDealHandler;
use Symfony\Component\EventDispatcher\Event;
use Heystack\Deals\Interfaces\DealHandlerInterface;

class TotalUpdatedEvent extends Event
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