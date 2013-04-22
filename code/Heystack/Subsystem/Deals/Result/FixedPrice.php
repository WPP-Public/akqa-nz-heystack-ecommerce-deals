<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Events;

use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Heystack\Subsystem\Deals\Traits\ResultTrait;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class FixedPrice implements ResultInterface
{
    use ResultTrait;

    protected $eventService;
    protected $purchasableHolder;
    protected $purchasable;
    protected $value;
    protected $configuration;


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

    public function description()
    {
        $this->purchasable = $this->purchasableHolder->getPurchasable(
            $this->configuration->getConfig('purchasable_identifier')
        );

        return 'The product (' . $this->purchasable->getIdentifier()->getPrimary() . ') is now priced at ' . $this->value;
    }

    public function process()
    {
        $this->purchasable = $this->purchasableHolder->getPurchasable(
            $this->configuration->getConfig('purchasable_identifier')
        );

        $originalTotal = $this->purchasable->getTotal();

        $this->purchasable->setUnitPrice($this->value);

        $this->eventService->dispatch(Events::RESULT_PROCESSED);

        return $originalTotal - $this->purchasable->getTotal();

    }

}
