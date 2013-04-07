<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Deals\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class RelativePrice extends FixedPrice
{    
    protected $calculatedPrice;
    
    public function __construct(EventDispatcherInterface $eventService, PurchasableHolderInterface $purchasableHolder, AdaptableConfigurationInterface $configuration)
    {
        parent::__construct($eventService, $purchasableHolder, $configuration);
        
        $this->calculatedPrice = ($this->purchasable->getPrice() / 100) * $this->value;
    }
    
    public function description()
    {
        return 'The product (' . $this->purchasable->getIdentifier() . ') is now priced at ' . $this->calculatedPrice;
    }
    
    public function process()
    {
        $originalTotal = $this->purchasable->getTotal();
        
        $this->purchasable->setUnitPrice($this->calculatedPrice);
        
        $this->eventService->dispatch(Events::RESULT_PROCESSED);

        return $originalTotal - $this->purchasable->getTotal();
        
    }

}
