<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class FixedPrice implements ResultInterface
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;
    /**
     * @var \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    protected $purchasableHolder;
    /**
     * @var
     */
    protected $purchasables;
    /**
     * @var
     */
    protected $value;
    /**
     * @var \Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface
     */
    protected $configuration;

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
        $this->eventService = $eventService;
        $this->purchasableHolder = $purchasableHolder;
        $this->configuration = $configuration;

        if (!$configuration->hasConfig('purchasable_identifier')) {

            throw new \Exception('Fixed Price Result requires a purchasable_identifier configuration value');

        }

        if ($configuration->hasConfig('value')) {

            $this->value = $configuration->getConfig('value');

        } else {

            throw new \Exception('Fixed Price Result requires a value configuration value');

        }

    }

    /**
     * @return string
     */
    public function getDescription()
    {
//        $this->purchasable = $this->purchasableHolder->getPurchasable(
//            $this->configuration->getConfig('purchasable_identifier')
//        );
//
//        return 'The product (' . $this->purchasable->getIdentifier()->getFull() . ') is now priced at ' . $this->value;
        return 'under development';
    }

    /**
     * @param DealHandlerInterface $dealHandler
     * @return int
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $this->purchasable = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier(
            new Identifier($this->configuration->getConfig('purchasable_identifier'))
        );

        $totalDiscount = 0;

        foreach ($this->purchasables as $purchasable) {

            $originalTotal = $purchasable->getTotal();

            $purchasable->setUnitPrice($this->value);

            $totalDiscount += $originalTotal - $purchasable->getTotal();

        }

        $this->eventService->dispatch(Events::RESULT_PROCESSED);

        return $totalDiscount;

    }
}
