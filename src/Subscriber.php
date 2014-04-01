<?php
/**
 * This file is part of the Ecommerce-Tax package
 *
 * @package Ecommerce-Deals
 */

/**
 * Tax namespace
 */
namespace Heystack\Deals;

use Heystack\Core\State\State;
use Heystack\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Core\Storage\Event as StorageEvent;
use Heystack\Core\Storage\Storage;
use Heystack\Core\Traits\HasEventServiceTrait;
use Heystack\Core\Traits\HasStateServiceTrait;
use Heystack\Deals\Events\TotalUpdatedEvent;
use Heystack\Deals\Interfaces\DealHandlerInterface;
use Heystack\Ecommerce\Currency\Events as CurrencyEvents;
use Heystack\Ecommerce\Locale\Events as LocaleEvents;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Ecommerce\Transaction\Events as TransactionEvents;
use Heystack\Purchasable\PurchasableHolder\Events as PurchasableHolderEvents;
use Heystack\Purchasable\PurchasableHolder\Traits\HasPurchasableHolderTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles both subscribing to events and acting on those events needed for TaxHandler to work properly
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Tax
 * @see Symfony\Component\EventDispatcher
 */
class Subscriber implements EventSubscriberInterface
{
    use HasEventServiceTrait;
    use HasPurchasableHolderTrait;
    use HasStateServiceTrait;

    /**
     * Holds the Deal Handler
     * @var \Heystack\Deals\Interfaces\DealHandlerInterface
     */
    protected $dealHandler;

    /**
     * Holds the storage service
     * @var \Heystack\Core\Storage\Storage
     */
    protected $storageService;
    
    protected $currencyChanging;

    /**
     * Creates the ShippingHandler Subscriber object
     * @param EventDispatcherInterface $eventService
     * @param Storage $storageService
     * @param PurchasableHolderInterface $purchasableHolder
     * @param \Heystack\Core\State\State $stateService
     * @param DealHandlerInterface $dealHandler
     */
    public function __construct(
        EventDispatcherInterface $eventService,
        Storage $storageService,
        PurchasableHolderInterface $purchasableHolder,
        State $stateService,
        DealHandlerInterface $dealHandler
    )
    {
        $this->eventService = $eventService;
        $this->storageService = $storageService;
        $this->purchasableHolder = $purchasableHolder;
        $this->stateService = $stateService;
        $this->dealHandler = $dealHandler;
    }

    /**
     * Returns an array of events to subscribe to and the methods to call when those events are fired
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::TOTAL_UPDATED                                            => ['onTotalUpdated', 0],
            CurrencyEvents::CHANGED                                          => ['onCurrencyChanged', 0],
            LocaleEvents::CHANGED                                            => ['onUpdateTotal', 0],
            PurchasableHolderEvents::PURCHASABLE_ADDED                       => ['onUpdateTotal', 0],
            PurchasableHolderEvents::PURCHASABLE_CHANGED                     => ['onUpdateTotal', 0],
            PurchasableHolderEvents::PURCHASABLE_REMOVED                     => ['onUpdateTotal', 0],
            sprintf('%s.%s', Backend::IDENTIFIER, TransactionEvents::STORED) => ['onTransactionStored', 10]
        ];
    }

    /**
     * Called to update the Deal Handler's total
     */
    public function onUpdateTotal()
    {
        $this->dealHandler->updateTotal();
    }

    /**
     * Called after the TaxHandler's total is updated.
     * Tells the transaction to update its total.
     */
    public function onTotalUpdated(TotalUpdatedEvent $event)
    {
        if ($event->getDealHandler()->getIdentifier()->isMatch($this->dealHandler->getIdentifier()) && !$this->currencyChanging) {
            $this->eventService->dispatch(TransactionEvents::UPDATE);
        }
    }
    
    public function onCurrencyChanged()
    {
        $this->currencyChanging = true;
        $this->dealHandler->updateTotal();
        $this->currencyChanging = false;
    }

    /**
     * After the transaction is stored, store the deal.
     * @param StorageEvent $event
     */
    public function onTransactionStored(StorageEvent $event)
    {
        if ($this->dealHandler->getTotal()->getAmount() > 0){
            $this->dealHandler->setParentReference($event->getParentReference());
            $this->storageService->process($this->dealHandler);
            $this->eventService->dispatch(Events::STORED);
            $this->stateService->removeByKey($this->dealHandler->getIdentifier()->getFull());
        }
    }
}
