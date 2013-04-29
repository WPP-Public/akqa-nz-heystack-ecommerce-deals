<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
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
    /**
     * @param EventDispatcherInterface        $eventService
     * @param PurchasableHolderInterface      $purchasableHolder
     * @param AdaptableConfigurationInterface $configuration
     */
    public function __construct(
        EventDispatcherInterface $eventService,
        PurchasableHolderInterface $purchasableHolder,
        AdaptableConfigurationInterface $configuration
    ) {
        parent::__construct($eventService, $purchasableHolder, $configuration);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Percentage Discount: ' . $this->value;
    }
    /**
     * @param DealHandlerInterface $dealHandler
     * @return float|int
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $this->purchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier(
            new Identifier($this->configuration->getConfig('purchasable_identifier'))
        );

        $totalDiscount = 0;

        foreach ($this->purchasables as $purchasable) {

            $discount = ($purchasable->getPrice() / 100) * $this->value;

            $newPrice = $purchasable->getPrice() - $discount;

            $purchasable->setUnitPrice($newPrice);

            $totalDiscount += $discount * $purchasable->getQuantity();

        }

        $this->eventService->dispatch(Events::RESULT_PROCESSED);

        return $totalDiscount;

    }
}
