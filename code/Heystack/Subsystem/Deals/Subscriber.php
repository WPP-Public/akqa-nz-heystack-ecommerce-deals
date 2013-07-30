<?php
/**
 * This file is part of the Ecommerce-Tax package
 *
 * @package Ecommerce-Deals
 */

/**
 * Tax namespace
 */
namespace Heystack\Subsystem\Deals;

use Heystack\Subsystem\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Subsystem\Core\Storage\Storage;
use Heystack\Subsystem\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Heystack\Subsystem\Ecommerce\Currency\Events as CurrencyEvents;
use Heystack\Subsystem\Ecommerce\Locale\Events as LocaleEvents;
use Heystack\Subsystem\Ecommerce\Transaction\Events as TransactionEvents;
use Heystack\Subsystem\Products\ProductHolder\Events as ProductHolderEvents;
use Heystack\Subsystem\Core\Storage\Event as StorageEvent;

use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;

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
    /**
     * Holds the Event Dispatcher Service
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;

    /**
     * Holds the Deal Handler
     * @var \Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface
     */
    protected $dealHandler;

    /**
     * Holds the storage service
     * @var \Heystack\Subsystem\Core\Storage\Storage
     */
    protected $storageService;

    /**
     * @var \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    protected $purchasableHolder;

    /**
     * Creates the ShippingHandler Subscriber object
     * @param EventDispatcherInterface $eventService
     * @param Storage $storageService
     * @param DealHandlerInterface $dealHandler
     */
    public function __construct(EventDispatcherInterface $eventService, Storage $storageService, PurchasableHolderInterface $purchasableHolder, DealHandlerInterface $dealHandler)
    {
        $this->eventService = $eventService;
        $this->dealHandler = $dealHandler;
        $this->storageService = $storageService;
        $this->purchasableHolder = $purchasableHolder;
    }

    /**
     * Returns an array of events to subscribe to and the methods to call when those events are fired
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            CurrencyEvents::CHANGED                                 => array('onUpdateTotal', 1),
            LocaleEvents::CHANGED                                   => array('onUpdateTotal', 1),
            ProductHolderEvents::PURCHASABLE_ADDED                            => array('onUpdateTotal', 1),
            ProductHolderEvents::PURCHASABLE_CHANGED                            => array('onUpdateTotal', 1),
            ProductHolderEvents::PURCHASABLE_REMOVED                            => array('onUpdateTotal', 1),
            Events::TOTAL_UPDATED                                   => array('onTotalUpdated', 1),
            Events::CONDITIONS_NOT_MET                              => array('onConditionsNotMet', 1),
            Backend::IDENTIFIER . '.' . TransactionEvents::STORED   => array('onTransactionStored', 10)
        );
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
    public function onTotalUpdated()
    {
        $this->eventService->dispatch(TransactionEvents::UPDATE);
    }

    public function onConditionsNotMet(Event $event)
    {
        $purchasables = $this->purchasableHolder->getPurchasables();

        if(is_array($purchasables) && count($purchasables)){

            foreach($purchasables as $purchasable){

                if($purchasable instanceof DealPurchasableInterface){

                    if ($this->dealHandler->getIdentifier() == $event->getIdentifier()) {

                        $purchasable->setFreeQuantity($this->dealHandler->getIdentifier(), 0);

                    }

                }
            }

        }

        $this->purchasableHolder->saveState();
    }

    /**
     * After the transaction is stored, store the deal.
     * @param StorageEvent $event
     */
    public function onTransactionStored(StorageEvent $event)
    {
        if($this->dealHandler->getTotal() > 0 ){

            $this->dealHandler->setParentReference($event->getParentReference());

            $this->storageService->process($this->dealHandler);

            $this->eventService->dispatch(Events::STORED);

        }
    }
}
