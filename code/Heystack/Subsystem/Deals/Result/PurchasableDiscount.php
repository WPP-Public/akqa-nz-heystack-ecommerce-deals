<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Deals\Events\ResultEvent;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Subsystem\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\HasPurchasableHolderInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Deals\Traits\HasDealHandler;
use Heystack\Subsystem\Deals\Traits\HasPurchasableHolder;
use Heystack\Subsystem\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class PurchasableDiscount implements ResultInterface, HasPurchasableHolderInterface, HasDealHandlerInterface
{
    use HasPurchasableHolder;
    use HasDealHandler;

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
     * @var array of \Heystack\Subsystem\Core\Identifier\Identifier
     */
    protected $purchasableIdentifiers = array();
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;
    /**
     * @var \Heystack\Subsystem\Ecommerce\Currency\Interfaces\CurrencyServiceInterface
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
        return array();
    }

    /**
     * Returns a short string that describes what the result does
     */
    public function getDescription()
    {
        return 'Purchasable Discount: Discount of ' . $this->getTotal();
    }

    /**
     * Main function that determines what the result does
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $this->eventService->dispatch(Events::RESULT_PROCESSED, new ResultEvent($this));
        return $this->getTotal();
    }

    /**
     * Calculates the total discounts
     *
     * @return mixed
     */
    protected function getTotal()
    {
        if (is_array($this->discountAmounts) && count($this->discountAmounts)) {

            $currencyCode = $this->currencyService->getActiveCurrencyCode();

            $discount = isset($this->discountAmounts[$currencyCode]) ? $this->discountAmounts[$currencyCode] : 0;

            $quantity = 0;

            foreach ($this->purchasableIdentifiers as $purchasableIdentifier) {

                $quantity += $this->getPurchasableIdentifierQuantity($purchasableIdentifier, $discount);

            }

            return $quantity * $discount;

        }

        if ($this->discountPercentage) {

            $total = 0;

            foreach ($this->purchasableIdentifiers as $purchasableIdentifier) {

                $total += $this->getPurchasableIdentifierTotal($purchasableIdentifier);

            }

            return (($total / 100) * $this->discountPercentage);

        }

        return 0;
    }

    /**
     * Gets the total price from the purchasable holder based on the primary identifier and sets the
     * deal discount amount on the purchasable
     *
     * @param Identifier $purchasableIdentifier
     * @return float
     */
    protected function getPurchasableIdentifierTotal(Identifier $purchasableIdentifier)
    {
        $total = 0;

        $purchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier($purchasableIdentifier);

        if (is_array($purchasables) && count($purchasables)) {

            foreach ($purchasables as $purchasable) {

                if ($purchasable instanceof DealPurchasableInterface) {

                    $total += $purchasable->getTotal();

                    $purchasable->setDealDiscount(
                        $this->getDealHandler()->getIdentifier(),
                        ($purchasable->getTotal() / 100) * $this->discountPercentage
                    );

                }

            }

        }

        return $total;
    }

    /**
     * Gets the total quantity from the purchasable holder based on the primary identifier and sets the
     * deal discount amount on the purchasable
     *
     * @param Identifier $purchasableIdentifier
     * @param float $discountAmount
     * @return int
     */
    protected function getPurchasableIdentifierQuantity(Identifier $purchasableIdentifier, $discountAmount)
    {
        $quantity = 0;

        $purchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier($purchasableIdentifier);

        if (is_array($purchasables) && count($purchasables)) {

            foreach ($purchasables as $purchasable) {

                if ($purchasable instanceof DealPurchasableInterface) {

                    $quantity += $purchasable->getQuantity();

                    $purchasable->setDealDiscount(
                        $this->getDealHandler()->getIdentifier(),
                        $discountAmount * $quantity
                    );

                }

            }

        }

        return $quantity;
    }
}