<?php

namespace Heystack\Deals\Result;

use Heystack\Core\Identifier\Identifier;
use Heystack\Deals\AdaptableConfiguration;
use Heystack\Deals\Condition;
use Heystack\Deals\Events\ConditionEvent;
use Heystack\Deals\Events;
use Heystack\Deals\Events\ResultEvent;
use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Deals\Interfaces\DealHandlerInterface;
use Heystack\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Deals\Interfaces\ResultInterface;
use Heystack\Deals\Interfaces\ResultWithConditionsInterface;
use Heystack\Deals\Traits\HasDealHandlerTrait;
use Heystack\Ecommerce\Currency\Traits\HasCurrencyServiceTrait;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableInterface;
use Heystack\Ecommerce\Transaction\TransactionModifierTypes;
use Heystack\Purchasable\PurchasableHolder\Interfaces\HasPurchasableHolderInterface;
use Heystack\Purchasable\PurchasableHolder\Traits\HasPurchasableHolderTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @package Heystack\Deals\Result
 */
class CheapestPurchasableDiscount
    implements
        ResultInterface,
        ResultWithConditionsInterface,
        HasPurchasableHolderInterface,
        HasDealHandlerInterface
{
    use HasDealHandlerTrait;
    use HasPurchasableHolderTrait;
    use HasCurrencyServiceTrait;

    const RESULT_TYPE = 'CheapestPurchasableDiscount';
    const PURCHASABLE_IDENTIFIER_STRINGS = 'purchasable_identifier_strings';

    /**
     * @var array
     */
    protected $purchasableIdentifiers = [];

    /**
     * @var \SebastianBergmann\Money\Money
     */
    protected $totalDiscount;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;

    /**
     * @param EventDispatcherInterface $eventService
     * @param PurchasableHolderInterface $purchasableHolder
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception
     */
    public function __construct(
        EventDispatcherInterface $eventService,
        PurchasableHolderInterface $purchasableHolder,
        AdaptableConfigurationInterface $configuration
    )
    {
        $this->eventService = $eventService;
        $this->purchasableHolder = $purchasableHolder;

        if ($configuration->hasConfig(self::PURCHASABLE_IDENTIFIER_STRINGS)) {

            $purchasableIdentifierStrings = $configuration->getConfig(self::PURCHASABLE_IDENTIFIER_STRINGS);

            if (is_array($purchasableIdentifierStrings) && count($purchasableIdentifierStrings)) {

                foreach ($purchasableIdentifierStrings as $purchasableIdentifierString) {

                    $this->purchasableIdentifiers[] = new Identifier($purchasableIdentifierString);

                }

            } else {

                throw new \Exception('Cheapest Purchasable Discount Result requires that the purchasable identifier strings are itemized in an array');

            }

        }

    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::CONDITIONS_NOT_MET => 'onConditionsNotMet'
        ];
    }

    /**
     * Returns a short string that describes what the result does
     */
    public function getDescription()
    {
        $this->process($this->getDealHandler());
        return sprintf(
            "Cheapest Purchasable Discount: Discount of '%s'",
            $this->totalDiscount->getAmount() / $this->totalDiscount->getCurrency()->getSubUnit()
        );
    }

    /**
     * Main function that determines what the result does
     * @param \Heystack\Deals\Interfaces\DealHandlerInterface $dealHandler
     * @return \SebastianBergmann\Money\Money
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $discount = $this->getCurrencyService()->getZeroMoney();
        $dealIdentifier = $dealHandler->getIdentifier();

        // Reset the free count for this deal of all the purchasables. We need to do this because
        // a new 'cheaper' product may have been added to the purchasable holder in the meantime
        foreach ($this->purchasableHolder->getPurchasables() as $purchasable) {
            if ($purchasable instanceof DealPurchasableInterface) {
                $purchasable->setFreeQuantity($dealIdentifier, 0);
                $purchasable->setDealDiscount($dealIdentifier, $discount);
            }
        }

        $remainingFreeDiscounts = $dealHandler->getConditionsMetCount();
        $purchasables = $this->getPurchsablesSortedByUnitPrice();

        $purchasable = current($purchasables);
        
        while ($purchasable && $remainingFreeDiscounts > 0) {
            // Skip if it isn't a deal purchasable
            if (!$purchasable instanceof DealPurchasableInterface) {
                $purchasable = next($purchasable);
                continue;
            }

            // Get the smaller number, either the quantity of the current purchasable
            // or the remaining free discounts
            $freeQuantity = min($purchasable->getQuantity(), $remainingFreeDiscounts);
            
            // Get the current discount
            $currentPurchasableDiscount = $purchasable->getDealDiscountWithExclusions([
                $dealIdentifier->getFull()
            ]);

            $purchasableTotal = $purchasable->getTotal();
            $purchasableDiscount = $purchasable->getUnitPrice()->multiply($freeQuantity);
            
            // When the deduction exceeds the remaining money just remove the remaining money
            if ($currentPurchasableDiscount->add($purchasableDiscount)->greaterThan($purchasableTotal)) {
                $purchasableDiscount = $purchasableTotal->subtract($currentPurchasableDiscount);
            }

            $discount = $discount->add($purchasableDiscount);

            $purchasable->setFreeQuantity($dealIdentifier, $freeQuantity);
            $purchasable->setDealDiscount(
                $dealHandler->getIdentifier(),
                $purchasableDiscount
            );

            $remainingFreeDiscounts -= $freeQuantity;

            // Advance to the next purchasable
            $purchasable = next($purchasables);
        }

        $this->purchasableHolder->updateTotal();
        $this->eventService->dispatch(Events::RESULT_PROCESSED, new ResultEvent($this));

        return $this->totalDiscount = $discount;
    }

    /**
     * Remove the deals effects
     * @param ConditionEvent $event
     */
    public function onConditionsNotMet(ConditionEvent $event)
    {
        $eventDealHandler = $event->getDealHandler();
        $eventDealIdentifier = $eventDealHandler->getIdentifier();
        $dealIdentifier = $this->getDealHandler()->getIdentifier();

        if ($dealIdentifier->isMatch($eventDealIdentifier)) {
            foreach ($this->getPurchasables() as $purchasable) {
                if ($purchasable instanceof DealPurchasableInterface) {
                    $purchasable->setFreeQuantity($dealIdentifier, 0);
                    $purchasable->setDealDiscount($dealIdentifier, $this->getCurrencyService()->getZeroMoney());
                }
            }
        }

        // We need to save the purchasable Holder's state because it keeps track of the state of each purchasable
        // in the transaction.
        $this->purchasableHolder->saveState();
    }

    /**
     * @return \Heystack\Deals\Interfaces\DealPurchasableInterface[]
     */
    protected function getPurchsablesSortedByUnitPrice()
    {
        $purchasables = $this->getPurchasables();
        
        usort($purchasables, function (PurchasableInterface $a, PurchasableInterface $b) {
            return $a->getUnitPrice()->compareTo($b->getUnitPrice());
        });
        
        return $purchasables;
    }

    /**
     * @return array|\Heystack\Ecommerce\Purchasable\Interfaces\PurchasableInterface[]
     */
    protected function getPurchasables()
    {
        $purchasables = [];

        if (count($this->purchasableIdentifiers)) {

            foreach ($this->purchasableIdentifiers as $purchasableIdentifier) {

                $purchasableHolderPurchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier($purchasableIdentifier);

                if (is_array($purchasableHolderPurchasables)) {

                    $purchasables[] = $purchasableHolderPurchasables;

                }

            }
            
            if (count($purchasables) > 0) {
                $purchasables = call_user_func_array('array_merge', $purchasables);
            }

        } else {

            $purchasables = $this->purchasableHolder->getPurchasables();

        }

        return $purchasables;
    }

    public function getConditions()
    {
        $productConfig = [
            Condition\PurchasableHasQuantityInCart::PURCHASABLE_IDENTIFIERS => $this->purchasableIdentifiers,
            Condition\PurchasableHasQuantityInCart::MINIMUM_QUANTITY_KEY => 1
        ];

        $purchasableInCartCondition = new Condition\PurchasableHasQuantityInCart(
            $this->getPurchasableHolder(),
            new AdaptableConfiguration($productConfig)
        );

        return [
            $purchasableInCartCondition
        ];
    }

    /**
     * @return \Heystack\Ecommerce\Transaction\Interfaces\TransactionModifierInterface[]
     */
    public function getLinkedModifiers()
    {
        return [$this->purchasableHolder];
    }

    /**
     * Indicates the type of amount the modifier will return
     * Must return a constant from TransactionModifierTypes
     * @return string
     */
    public function getType()
    {
        return TransactionModifierTypes::DEDUCTIBLE;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 100;
    }
}

