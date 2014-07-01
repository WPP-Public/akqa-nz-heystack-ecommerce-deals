<?php

namespace Heystack\Deals\Result;

use Heystack\Core\Identifier\Identifier;
use Heystack\Core\Traits\HasEventServiceTrait;
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
use SebastianBergmann\Money\Money;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class PurchasableDiscount
    implements
        ResultInterface,
        HasPurchasableHolderInterface,
        HasDealHandlerInterface
{
    use HasPurchasableHolderTrait;
    use HasDealHandlerTrait;
    use HasCurrencyServiceTrait;
    use HasEventServiceTrait;

    const RESULT_TYPE = 'PurchasableDiscount';
    const PURCHASABLE_DISCOUNT_AMOUNTS = 'purchasable_discount_amounts';
    const PURCHASABLE_DISCOUNT_PERCENTAGE = 'purchasable_discount_percentage';
    const PURCHASABLE_IDENTIFIER_STRINGS = 'purchasable_identifier_strings';

    /**
     * @var array of amounts indexed by currency code
     */
    protected $discountAmounts;

    /**
     * @var float
     */
    protected $discountPercentage;

    /**
     * @var \Heystack\Core\Identifier\IdentifierInterface[]
     */
    protected $purchasableIdentifiers = [];

    /**
     * @param EventDispatcherInterface $eventService
     * @param PurchasableHolderInterface $purchasableHolder
     * @param CurrencyServiceInterface $currencyService
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception when configured incorrectly
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

        $discountConfigured = false;

        if ($configuration->hasConfig(self::PURCHASABLE_DISCOUNT_AMOUNTS)) {

            if ($discountConfigured) {

                throw new \Exception('Purchasable Discount Result requires that only one discount is configured. Please remove one of the following: ' . $discountConfigured . ',  ' . self::PURCHASABLE_DISCOUNT_AMOUNTS);

            }

            $discountAmounts = $configuration->getConfig(self::PURCHASABLE_DISCOUNT_AMOUNTS);

            if (is_array($discountAmounts) && count($discountAmounts)) {

                $this->discountAmounts = $discountAmounts;
                $discountConfigured = self::PURCHASABLE_DISCOUNT_AMOUNTS;

            } else {

                throw new \Exception('Purchasable Discount Result requires that the discount amounts are configured using an array of amounts with their indexes being the currency code.');

            }


        }

        if ($configuration->hasConfig(self::PURCHASABLE_DISCOUNT_PERCENTAGE)) {

            if ($discountConfigured) {

                throw new \Exception('Purchasable Discount Result requires that only one discount is configured. Please remove one of the following: ' . $discountConfigured . ',  ' . self::PURCHASABLE_DISCOUNT_PERCENTAGE);

            }

            $this->discountPercentage = $configuration->getConfig(self::PURCHASABLE_DISCOUNT_PERCENTAGE);

        }

        if ($configuration->hasConfig(self::PURCHASABLE_IDENTIFIER_STRINGS)) {

            $purchasableIdentifierStrings = $configuration->getConfig(self::PURCHASABLE_IDENTIFIER_STRINGS);

            if (is_array($purchasableIdentifierStrings) && count($purchasableIdentifierStrings)) {

                foreach ($purchasableIdentifierStrings as $purchasableIdentifierString) {

                    $this->purchasableIdentifiers[] = new Identifier($purchasableIdentifierString);

                }

            } else {

                throw new \Exception('Purchasable Discount Result requires that the purchasable identifier strings are itemized in an array');

            }

        } else {

            throw new \Exception('Purchasable Discount Result requires a purchasable identifier string configuration');

        }
    }

    public static function getSubscribedEvents()
    {
        return [];
    }

    /**
     * Returns a short string that describes what the result does
     */
    public function getDescription()
    {
        $total = $this->getTotal();
        return 'Purchasable Discount: Discount of ' . ($total->getAmount() / $total->getCurrency()->getSubUnit());
    }

    /**
     * Main function that determines what the result does
     * @param \Heystack\Deals\Interfaces\DealHandlerInterface $dealHandler
     * @return \SebastianBergmann\Money\Money
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $total = $this->getTotal();
        $this->eventService->dispatch(Events::RESULT_PROCESSED, new ResultEvent($this));
        return $total;
    }

    /**
     * Calculates the total discounts
     *
     * @return \SebastianBergmann\Money\Money
     */
    protected function getTotal()
    {
        $total = $this->currencyService->getZeroMoney();

        foreach ($this->purchasableIdentifiers as $identifier) {
            foreach ($this->purchasableHolder->getPurchasablesByPrimaryIdentifier($identifier) as $purchasable) {
                if ($purchasable instanceof DealPurchasableInterface) {
                    $dealDiscount = $this->getDealDiscountForPurchasable($purchasable);

                    $purchasable->setDealDiscount(
                        $this->getDealHandler()->getIdentifier(),
                        $dealDiscount
                    );

                    $total = $total->add($dealDiscount);
                }
            }
        }

        return $total;
    }

    /**
     * @param \Heystack\Deals\Interfaces\DealPurchasableInterface $purchasable
     * @return \SebastianBergmann\Money\Money
     */
    protected function getDealDiscountForPurchasable(DealPurchasableInterface $purchasable)
    {
        $purchasableTotal = $purchasable->getTotal();
        $purchasableCurrentDiscount = $purchasable->getDealDiscountWithExclusions([
            $this->getDealHandler()->getIdentifier()->getFull()
        ]);

        if (is_array($this->discountAmounts) && count($this->discountAmounts)) {
            $currency = $this->currencyService->getActiveCurrency();
            $currencyCode = $currency->getCurrencyCode();

            if ($this->discountAmounts[$currencyCode]) {
                $purchasableDiscount = new Money(intval($this->discountAmounts[$currencyCode] * $currency->getSubUnit()), $currency);
                $purchasableDiscount = $purchasableDiscount->multiply($purchasable->getQuantity());
            } else {
                $purchasableDiscount = $this->currencyService->getZeroMoney();
            }
            
        } elseif ($this->discountPercentage) {
            list($purchasableDiscount,) = $purchasableTotal->allocateByRatios(
                [$this->discountPercentage, 100 - $this->discountPercentage]
            );
        } else {
            $purchasableDiscount = $this->currencyService->getZeroMoney();
        }

        if ($purchasableCurrentDiscount->add($purchasableDiscount)->greaterThan($purchasableTotal)) {
            $dealDiscount = $purchasableTotal->subtract($purchasableCurrentDiscount);
        } else {
            $dealDiscount = $purchasableDiscount;
        }

        return $dealDiscount;
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
        return 50;
    }
}
