<?php

namespace Heystack\Deals;

use Heystack\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Purchasable\PurchasableHolder\Traits\HasPurchasableHolderTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Heystack\Purchasable\PurchasableHolder\Events as PurchasableHolderEvents;

/**
 * @package Heystack\Deals
 */
class PurchasableHolderSubscriber implements EventSubscriberInterface
{
    use HasPurchasableHolderTrait;

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
            PurchasableHolderEvents::PURCHASABLE_ADDED   => ['resetDealPurchasables', 1],
            PurchasableHolderEvents::PURCHASABLE_CHANGED => ['resetDealPurchasables', 1],
            PurchasableHolderEvents::PURCHASABLE_REMOVED => ['resetDealPurchasables', 1],
        ];
    }

    /**
     * 
     */
    public function resetDealPurchasables()
    {
        foreach ($this->purchasableHolder->getPurchasables() as $purchasable) {
            if ($purchasable instanceof DealPurchasableInterface) {
                $purchasable->removeDealDiscounts();
                $purchasable->removeFreeQuantities();
            }
        }
    }
}