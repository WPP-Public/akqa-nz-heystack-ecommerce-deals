<?php

namespace Heystack\Deals\Result;

use Heystack\Core\EventDispatcher;
use Heystack\Core\Identifier\Identifier;
use Heystack\Core\Traits\HasEventServiceTrait;
use Heystack\Deals\Events\ConditionEvent;
use Heystack\Deals\Events;
use Heystack\Deals\Events\ResultEvent;
use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Deals\Interfaces\DealHandlerInterface;
use Heystack\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Deals\Interfaces\ResultInterface;
use Heystack\Deals\Traits\HasDealHandlerTrait;
use Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Ecommerce\Currency\Traits\HasCurrencyServiceTrait;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Ecommerce\Transaction\TransactionModifierTypes;
use Heystack\Purchasable\PurchasableHolder\Interfaces\HasPurchasableHolderInterface;
use Heystack\Purchasable\PurchasableHolder\Traits\HasPurchasableHolderTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author     Glenn Bautista <glenn@heyday.co.nz>
 * @package    Ecommerce-Deals
 */
class FreeGift implements
    ResultInterface,
    HasDealHandlerInterface,
    HasPurchasableHolderInterface
{
    use HasDealHandlerTrait;
    use HasPurchasableHolderTrait;
    use HasEventServiceTrait;
    use HasCurrencyServiceTrait;

    const RESULT_TYPE = 'FreeGift';
    const PURCHASABLE_CLASS = 'purchasable_class';
    const PURCHASABLE_ID = 'purchasable_id';
    /**
     * @var string
     */
    protected $purchasableClass;
    /**
     * @var int
     */
    protected $purchasableID;

    /**
     * @param EventDispatcherInterface $eventService
     * @param PurchasableHolderInterface $purchasableHolder
     * @param AdaptableConfigurationInterface $configuration
     * @param CurrencyServiceInterface $currencyService
     * @throws \Exception if the configuration is incorrect
     */
    public function __construct(
        EventDispatcherInterface $eventService,
        PurchasableHolderInterface $purchasableHolder,
        CurrencyServiceInterface $currencyService,
        AdaptableConfigurationInterface $configuration
    ) {
        $this->eventService = $eventService;
        $this->purchasableHolder = $purchasableHolder;
        $this->currencyService = $currencyService;

        if ($configuration->hasConfig(self::PURCHASABLE_CLASS)) {

            $this->purchasableClass = $configuration->getConfig(self::PURCHASABLE_CLASS);

        } else {

            throw new \Exception('Free Gift Result requires a purchasable_class configuration value');

        }

        if ($configuration->hasConfig(self::PURCHASABLE_ID)) {

            $this->purchasableID = $configuration->getConfig(self::PURCHASABLE_ID);

        } else {

            throw new \Exception('Free Gift Result requires a purchasable_id configuration value');

        }
    }

    public static function getSubscribedEvents()
    {
        return [
            Events::CONDITIONS_NOT_MET => 'onConditionsNotMet',
            Events::CONDITIONS_MET     => 'onConditionsMet'
        ];
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Free Purchasable: ' . $this->purchasableClass . $this->purchasableID;
    }

    /**
     * @param DealHandlerInterface $dealHandler
     * @return mixed
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $purchasable = $this->getPurchasable();
        $purchasableTotal = $purchasable->getTotal();
        $currentDiscountTotal = $purchasable->getDealDiscountWithExclusions([
            $this->getDealHandler()->getIdentifier()->getFull()
        ]);

        if ($purchasable instanceof $this->purchasableClass) {
            $total = $this->getPurchasable()->getUnitPrice()->multiply($dealHandler->getConditionsMetCount());
        } else {
            $total = $this->currencyService->getZeroMoney();
        }

        if ($total->add($currentDiscountTotal)->greaterThan($purchasableTotal)) {
            $total = $purchasableTotal->subtract($currentDiscountTotal);
        }

        $this->eventService->dispatch(Events::RESULT_PROCESSED, new ResultEvent($this));

        return $total;
    }

    /**
     * Applies the free gift to the purchasable holder.
     *
     * The event dispatcher is disabled here to not cause recursion as the free gift is added to the cart. In normal
     * circumstances adding a purchasable to the purschasable holder causes deal conditions to be re-evaluated, and
     * results of those conditions applied. Obviously in this situation, adding a free gift to the cart may cause those
     * events to fire causing other purchasables to be added as a result - leading to a bad situation
     *
     * There are two cases where the free gift must be added the purchasable holder
     *
     *  1) when the purchasable has already been added to the cart, but none are yet free.
     *  2) when the purchasable has already been added to the cart but the purchasables free quantity is less than the
     *     current amount of times this condition has been met.
     *
     * @param ConditionEvent $event
     * @param $eventName
     * @param \Heystack\Core\EventDispatcher $dispatcher
     */
    public function onConditionsMet(ConditionEvent $event, $eventName, EventDispatcher $dispatcher)
    {
        // Should we get the event dispatcher off the event?
        $deal = $this->getDealHandler();
        $dealIdentifier = $deal->getIdentifier();
        $conditionsMetCount = $deal->getConditionsMetCount();
        $purchasableHolder = $this->getPurchasableHolder();

        // Only do stuff if it is relevant to this deal
        if ($dealIdentifier->isMatch($event->getDealHandler()->getIdentifier())) {

            $dispatcher->setEnabled(false);

            $purchasable = $this->getPurchasable();

            if ($purchasable instanceof DealPurchasableInterface) {
                $purchasableAlreadyInCart = $this->purchasableHolder->getPurchasable($purchasable->getIdentifier());

                if (!$purchasableAlreadyInCart instanceof DealPurchasableInterface) {
                    $this->purchasableHolder->setPurchasable(
                        $purchasable,
                        0
                    );
                }

                $purchasable->setFreeQuantity($dealIdentifier, $conditionsMetCount);
            }

            $dispatcher->setEnabled(true);
            $purchasableHolder->updateTotal();
        }
    }

    public function onConditionsNotMet(ConditionEvent $event)
    {
        $dealIdentifier = $this->getDealHandler()->getIdentifier();

        if ($dealIdentifier->isMatch($event->getDealHandler()->getIdentifier())) {
            if (($purchasable = $this->getPurchasable()) instanceof DealPurchasableInterface) {
                $purchasable->setFreeQuantity($dealIdentifier, 0);
                if ($purchasable->getQuantity() == 0) {
                    $this->purchasableHolder->removePurchasable($purchasable->getIdentifier());
                }
            }
        }

        $this->purchasableHolder->saveState();
    }

    /**
     * Retrieve the purchasable either from the purchasable holder or the data store
     *
     * @return \Heystack\Deals\Interfaces\DealPurchasableInterface
     */
    public function getPurchasable()
    {
        $purchasable = $this->purchasableHolder->getPurchasable(
            new Identifier($this->purchasableClass . $this->purchasableID)
        );

        if (!$purchasable instanceof DealPurchasableInterface) {

            $purchasable = \DataList::create($this->purchasableClass)->byID($this->purchasableID);

            if ($purchasable instanceof DealPurchasableInterface) {
                $purchasable->setUnitPrice($purchasable->getPrice());
            }

        }

        return $purchasable;
    }

    /**
     * Use the neutral type because the total is deducted
     * by the fact that the products aren't charged for
     * @return mixed
     */
    public function getType()
    {
        return TransactionModifierTypes::NEUTRAL;
    }

    /**
     * @return \Heystack\Ecommerce\Transaction\Interfaces\TransactionModifierInterface[]
     */
    public function getLinkedModifiers()
    {
        return [];
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 0;
    }
}
