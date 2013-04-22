<?php

namespace Heystack\Subsystem\Deals\Traits;

use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;

trait ResultTrait 
{
    protected $dealHandler;
    
    protected $dealIdentifier;

    /**
     * Set the deal handler on the result
     *
     * @param \Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface $dealHandler
     */
    public function setDealHandler(DealHandlerInterface $dealHandler)
    {
        $this->dealHandler = $dealHandler;
        
        $this->dealIdentifier = $dealHandler->getIdentifier()->getPrimary();
    }
}