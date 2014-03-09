<?php

namespace Heystack\Deals\Result;

use Heystack\Core\Identifier\Identifier;
use Heystack\Deals\Events;
use Heystack\Deals\Events\ResultEvent;
use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Deals\Interfaces\DealHandlerInterface;
use Heystack\Deals\Interfaces\ResultInterface;
use Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableInterface;
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
        HasPurchasableHolderInterface
{
    use HasPurchasableHolderTrait;

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
     * @var array of \Heystack\Core\Identifier\Identifier
     */
    protected $purchasableIdentifiers = [];
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;
    /**
     * @var \Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface
     */
    protected $currencyService;


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
            $discountConfigured = self::PURCHASABLE_DISCOUNT_PERCENTAGE;

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
        $this->eventService->dispatch(Events::RESULT_PROCESSED, new ResultEvent($this));
        return $this->getTotal();
    }

    /**
     * Calculates the total discounts
     *
     * @return \SebastianBergmann\Money\Money
     */
    protected function getTotal()
    {
        if (is_array($this->discountAmounts) && count($this->discountAmounts)) {
            $currency = $this->currencyService->getActiveCurrency();
            $currencyCode = $currency->getCurrencyCode();
            
            if ($this->discountAmounts[$currencyCode]) {
                $discount = new Money($this->discountAmounts[$currencyCode] * $currency->getSubUnit(), $currency);
            } else {
                $discount = $this->currencyService->getZeroMoney();
            }

            $quantity = 0;

            foreach ($this->purchasableIdentifiers as $purchasableIdentifier) {

                $quantity += $this->getPurchasableIdentifierQuantity($purchasableIdentifier);

            }

            return $discount->multiply($quantity);
        }

        if ($this->discountPercentage) {
            $total = $this->currencyService->getZeroMoney();

            foreach ($this->purchasableIdentifiers as $purchasableIdentifier) {
                $total = $total->add($this->getPurchasableIdentifierTotal($purchasableIdentifier));
            }
            
            list($newTotal, ) = $total->allocateByRatios([$this->discountPercentage, 100 - $this->discountPercentage]);
            
            return $newTotal;
        }

        return $this->currencyService->getZeroMoney();
    }

    /**
     * Gets the total price from the purchasable holder based on the primary identifier
     *
     * @param Identifier $purchasableIdentifier
     * @return \SebastianBergmann\Money\Money
     */
    protected function getPurchasableIdentifierTotal(Identifier $purchasableIdentifier)
    {
        $total = $this->currencyService->getZeroMoney();

        $purchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier($purchasableIdentifier);

        if (is_array($purchasables) && count($purchasables)) {

            foreach ($purchasables as $purchasable) {

                if ($purchasable instanceof PurchasableInterface) {

                    $total = $total->add($purchasable->getTotal());

                }

            }

        }

        return $total;
    }

    /**
     * Gets the total quantity from the purchasable holder based on the primary identifier
     *
     * @param Identifier $purchasableIdentifier
     * @return int
     */
    protected function getPurchasableIdentifierQuantity(Identifier $purchasableIdentifier)
    {
        $quantity = 0;

        $purchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier($purchasableIdentifier);

        if (is_array($purchasables) && count($purchasables)) {

            foreach ($purchasables as $purchasable) {

                if ($purchasable instanceof PurchasableInterface) {

                    $quantity += $purchasable->getQuantity();

                }

            }

        }

        return $quantity;
    }
}