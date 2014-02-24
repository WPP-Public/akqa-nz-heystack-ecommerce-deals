<?php

namespace Heystack\Subsystem\Deals\Traits;

use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;

trait HasDealHandler
{
    /**
     * @var \Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface
     */
    protected $dealHandler;

    public function setDealHandler(DealHandlerInterface $dealHandler)
    {
        $this->dealHandler = $dealHandler;
    }

    /**
     * @return \Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface
     */
    public function getDealHandler()
    {
        return $this->dealHandler;
    }
}