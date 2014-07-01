<?php

namespace Heystack\Deals;

use Heystack\Core\EventDispatcher;
use Heystack\Core\Traits\HasEventServiceTrait;
use Heystack\Deals\Interfaces\DealHandlerInterface;
use Heystack\Purchasable\PurchasableHolder\Events as PurchasableHolderEvents;
use Heystack\Ecommerce\Currency\Events as CurrencyEvents;
use Heystack\Ecommerce\Locale\Events as LocaleEvents;
use Heystack\Ecommerce\Transaction\Events as TransactionEvents;
use Heystack\Deals\Events\TotalUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @package Heystack\Deals
 */
class DealsSubscriber implements EventSubscriberInterface
{
    use HasEventServiceTrait;

    /**
     * @var \Heystack\Deals\Interfaces\DealHandlerInterface[]
     */
    protected $dealHandlers = [];

    /**
     * @var bool
     */
    protected $currencyChanging;

    /**
     * @param EventDispatcher $eventService
     */
    public function __construct(EventDispatcher $eventService)
    {
        $this->eventService = $eventService;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     *
     * @api
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::TOTAL_UPDATED                        => ['onTotalUpdated', 0],
            CurrencyEvents::CHANGED                      => ['onCurrencyChanged', 0],
            LocaleEvents::CHANGED                        => ['updateTotals', 0],
            PurchasableHolderEvents::PURCHASABLE_ADDED   => ['updateTotals', 0],
            PurchasableHolderEvents::PURCHASABLE_CHANGED => ['updateTotals', 0],
            PurchasableHolderEvents::PURCHASABLE_REMOVED => ['updateTotals', 0],
            Events::COUPON_REMOVED                       => ['updateTotals', 0],
            Events::COUPON_ADDED                         => ['updateTotals', 0]
        ];
    }

    /**
     * @param \Heystack\Deals\Interfaces\DealHandlerInterface $dealHandler
     */
    public function addDealHandler(DealHandlerInterface $dealHandler)
    {
        $this->dealHandlers[$dealHandler->getIdentifier()->getFull()] = $dealHandler;
    }

    /**
     * @param \Heystack\Deals\Interfaces\DealHandlerInterface[] $dealHandlers
     */
    public function setDealHandlers(array $dealHandlers)
    {
        foreach ($dealHandlers as $dealHandler) {
            $this->addDealHandler($dealHandler);
        }
    }

    /**
     * @return \Heystack\Deals\Interfaces\DealHandlerInterface[]
     */
    public function getDealHandlers()
    {
        return $this->dealHandlers;
    }

    /**
     * @return \Heystack\Deals\Interfaces\DealHandlerInterface[]
     */
    protected function getDealHandlersOrderedByPriority()
    {
        $dealHandlers = $this->dealHandlers;
        
        usort($dealHandlers, function (DealHandlerInterface $a, DealHandlerInterface $b) {
            return $b->compareTo($a);
        });
        
        return $dealHandlers;
    }

    /**
     * Called after the TaxHandler's total is updated.
     * Tells the transaction to update its total.
     */
    public function onTotalUpdated(TotalUpdatedEvent $event)
    {
        $eventDealHandlerIdentifier = $event->getDealHandler()->getIdentifier()->getFull();
        if (!$this->currencyChanging && isset($this->dealHandlers[$eventDealHandlerIdentifier])) {
            $this->eventService->dispatch(TransactionEvents::UPDATE);
        }
    }

    /**
     * Update totals
     */
    public function updateTotals()
    {
        foreach ($this->getDealHandlersOrderedByPriority() as $dealHandler) {
            $dealHandler->updateTotal();
        }
    }

    /**
     * 
     */
    public function onCurrencyChanged()
    {
        $this->currencyChanging = true;
        $this->updateTotals();
        $this->currencyChanging = false;
    }
}