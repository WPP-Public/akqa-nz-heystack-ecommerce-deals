<?php

namespace Heystack\Deals\Traits;

use Heystack\Deals\Interfaces\DealHandlerInterface;

trait HasDealHandlerTrait
{
    /**
     * @var \Heystack\Deals\Interfaces\DealHandlerInterface
     */
    protected $dealHandler;

    /**
     * @param \Heystack\Deals\Interfaces\DealHandlerInterface $dealHandler
     * @return void
     */
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