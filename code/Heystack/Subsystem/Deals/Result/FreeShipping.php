<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Shipping\Interfaces\ShippingHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FreeShipping implements ResultInterface
{
    const IDENTIFIER = 'free_shipping';

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;
    /**
     * @var \Heystack\Subsystem\Shipping\Interfaces\ShippingHandlerInterface
     */
    protected $shippingService;
    /**
     * @var \Heystack\Subsystem\Core\State\State
     */
    protected $stateService;
    /**
     * @var \Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface
     */
    protected $configuration;
    /**
     * @var
     */
    protected $purchaseable;
    /**
     * @var bool
     */
    protected $processed = false;
    /**
     * @param EventDispatcherInterface        $eventService
     * @param ShippingHandlerInterface        $shippingService
     * @param AdaptableConfigurationInterface $configuration
     */
    public function __construct(
        EventDispatcherInterface $eventService,
        ShippingHandlerInterface $shippingService,
        AdaptableConfigurationInterface $configuration
    ) {
        $this->eventService = $eventService;
        $this->shippingService = $shippingService;
        $this->configuration = $configuration;
    }
    /**
     * Returns a short string that describes what the result does
     */
    public function getDescription()
    {
        return 'Free shipping: Discount of ' . $this->getTotal();
    }
    /**
     * Main function that determines what the result does
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $this->eventService->dispatch(Events::RESULT_PROCESSED);
        return $this->getTotal();
    }
    /**
     * @return mixed
     */
    protected function getTotal()
    {
        return $this->shippingService->getTotal();
    }
}
