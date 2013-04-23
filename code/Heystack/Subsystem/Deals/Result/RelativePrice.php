<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Core\Identifier\Identifier;
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

    public function __construct(
        EventDispatcherInterface $eventService,
        PurchasableHolderInterface $purchasableHolder,
        AdaptableConfigurationInterface $configuration
    ) {
        parent::__construct($eventService, $purchasableHolder, $configuration);
    }

    public function description()
    {
        return 'The product (' . $this->purchasable->getIdentifier()->getFull() . ') is now priced at ' . $this->calculatedPrice;
    }

    public function process()
    {
        $this->purchasable = $this->purchasableHolder->getPurchasable(
            new Identifier($this->configuration->getConfig('purchasable_identifier'))
        );

        $discount = ($this->purchasable->getPrice() / 100) * $this->value;

        $this->calculatedPrice = $this->purchasable->getPrice() - $discount;

        $originalTotal = $this->purchasable->getTotal();

        $this->purchasable->setUnitPrice($this->calculatedPrice);

        $this->eventService->dispatch(Events::RESULT_PROCESSED);

        return $originalTotal - $this->purchasable->getTotal();

    }

}
