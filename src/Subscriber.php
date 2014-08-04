<?php
/**
 * This file is part of the Ecommerce-Deals package
 *
 * @package Ecommerce-Deals
 */

/**
 * Deals namespace
 */
namespace Heystack\Deals;

use Heystack\Core\EventDispatcher;
use Heystack\Core\State\State;
use Heystack\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Core\Storage\Event as StorageEvent;
use Heystack\Core\Storage\Storage;
use Heystack\Core\Traits\HasEventServiceTrait;
use Heystack\Core\Traits\HasStateServiceTrait;
use Heystack\Deals\Interfaces\CouponHolderInterface;
use Heystack\Deals\Interfaces\CouponInterface;
use Heystack\Deals\Interfaces\DealHandlerInterface;
use Heystack\Deals\Interfaces\HasCouponHolderInterface;
use Heystack\Deals\Traits\HasCouponHolderTrait;
use Heystack\Ecommerce\Transaction\Events as TransactionEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Handles both subscribing to events and acting on those events needed for DealHandler to work properly
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 * @see Symfony\Component\EventDispatcher
 */
class Subscriber implements EventSubscriberInterface, HasCouponHolderInterface
{
    use HasEventServiceTrait;
    use HasStateServiceTrait;
    use HasCouponHolderTrait;

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

    /**
     * Creates the ShippingHandler Subscriber object
     * @param \Heystack\Core\EventDispatcher $eventService
     * @param \Heystack\Core\Storage\Storage $storageService
     * @param \Heystack\Core\State\State $stateService
     * @param \Heystack\Deals\Interfaces\CouponHolderInterface $couponHolder
     * @param \Heystack\Deals\Interfaces\DealHandlerInterface $dealHandler
     */
    public function __construct(
        EventDispatcher $eventService,
        Storage $storageService,
        State $stateService,
        CouponHolderInterface $couponHolder,
        DealHandlerInterface $dealHandler
    )
    {
        $this->eventService = $eventService;
        $this->storageService = $storageService;
        $this->stateService = $stateService;
        $this->couponHolder = $couponHolder;
        $this->dealHandler = $dealHandler;
    }

    /**
     * Returns an array of events to subscribe to and the methods to call when those events are fired
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            sprintf('%s.%s', Backend::IDENTIFIER, TransactionEvents::STORED) => ['onTransactionStored', 10]
        ];
    }

    /**
     * After the transaction is stored, store the deal.
     * @param \Heystack\Core\Storage\Event $event
     * @param string $eventName
     * @param \Heystack\Core\EventDispatcher $dispatcher
     * @return void
     */
    public function onTransactionStored(StorageEvent $event, $eventName, EventDispatcher $dispatcher)
    {
        if ($this->dealHandler->getConditionsMetCount() > 0) {
            $this->dealHandler->setParentReference($event->getParentReference());
            $results = $this->storageService->process($this->dealHandler);

            // Store the Coupons associated with the deal
            if (!empty($results[Backend::IDENTIFIER])) {
                $storedDeal = $results[Backend::IDENTIFIER];
                $coupons = $this->couponHolder->getCoupons($this->dealHandler->getIdentifier());

                foreach ($coupons as $coupon) {
                    if ($coupon instanceof CouponInterface) {
                        $coupon->setParentReference($storedDeal->ID);
                        $this->storageService->process($coupon);
                    }
                }

            }

            $this->eventService->dispatch(Events::STORED);
            $this->stateService->removeByKey($this->dealHandler->getIdentifier()->getFull());
        }
    }
}
