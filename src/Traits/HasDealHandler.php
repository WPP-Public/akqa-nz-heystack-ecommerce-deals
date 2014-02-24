<?php

namespace Heystack\Deals\Traits;

use Heystack\Deals\Interfaces\DealHandlerInterface;

trait HasDealHandler
{
    /**
     * @var \Heystack\Deals\Interfaces\DealHandlerInterface
     */
    protected $dealHandler;

    public function setDealHandler(DealHandlerInterface $dealHandler)
    {
        $this->dealHandler = $dealHandler;
    }

    /**
     * @return \Heystack\Deals\Interfaces\DealHandlerInterface
     */
    public function getDealHandler()
    {
        return $this->dealHandler;
    }
}