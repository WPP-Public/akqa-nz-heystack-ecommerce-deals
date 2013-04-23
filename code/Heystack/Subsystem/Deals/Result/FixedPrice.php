<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Events;

use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Heystack\Subsystem\Deals\Traits\ResultTrait;
use Heystack\Subsystem\Core\Identifier\Identifier;

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
    protected $purchasables;
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
//        $this->purchasable = $this->purchasableHolder->getPurchasable(
//            $this->configuration->getConfig('purchasable_identifier')
//        );
//
//        return 'The product (' . $this->purchasable->getIdentifier()->getFull() . ') is now priced at ' . $this->value;

        return 'under development';
    }

    public function process()
    {
        $this->purchasable = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier(
            new Identifier($this->configuration->getConfig('purchasable_identifier'))
        );

        $totalDiscount = 0;

        foreach($this->purchasables as $purchasable){

            $originalTotal = $purchasable->getTotal();

            $purchasable->setUnitPrice($this->value);

            $totalDiscount += $originalTotal - $purchasable->getTotal();

        }

        $this->eventService->dispatch(Events::RESULT_PROCESSED);

        return $totalDiscount;

    }

}
