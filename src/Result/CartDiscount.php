<?php

namespace Heystack\Deals\Result;

use Heystack\Deals\Events;
use Heystack\Deals\Events\ResultEvent;
use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Deals\Interfaces\DealHandlerInterface;
use Heystack\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Deals\Interfaces\ResultInterface;
use Heystack\Deals\Traits\HasDealHandler;
use Heystack\Deals\Traits\HasDealHandlerTrait;
use Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
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
class CartDiscount implements ResultInterface, HasPurchasableHolderInterface, HasDealHandlerInterface
{
    use HasPurchasableHolderTrait;
    use HasDealHandlerTrait;

    const RESULT_TYPE = 'CartDiscount';
    const CART_DISCOUNT_AMOUNTS = 'cart_discount_amounts';
    const CART_DISCOUNT_PERCENTAGE = 'cart_discount_percentage';

    /**
     * @var array
     */
    protected $discountAmounts;

    /**
     * @var
     */
    protected $discountPercentage;

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
    )
    {
        $this->eventService = $eventService;
        $this->purchasableHolder = $purchasableHolder;
        $this->currencyService = $currencyService;

        $discountConfigured = false;

        if ($configuration->hasConfig(self::CART_DISCOUNT_AMOUNTS)) {

            if ($discountConfigured) {

                throw new \Exception('Cart Discount Result requires that only one discount is configured. Please remove one of the following: ' . $discountConfigured . ',  ' . self::CART_DISCOUNT_AMOUNTS);

            }

            $discountAmounts = $configuration->getConfig(self::CART_DISCOUNT_AMOUNTS);

            if (is_array($discountAmounts) && count($discountAmounts)) {

                $this->discountAmounts = $discountAmounts;
                $discountConfigured = self::CART_DISCOUNT_AMOUNTS;

            } else {

                throw new \Exception('Cart Discount Result requires that the discount amounts are configured using an array of amounts with their indexes being the currency code.');

            }


        }

        if ($configuration->hasConfig(self::CART_DISCOUNT_PERCENTAGE)) {

            if ($discountConfigured) {

                throw new \Exception('Cart Discount Result requires that only one discount is configured. Please remove one of the following: ' . $discountConfigured . ',  ' . self::CART_DISCOUNT_PERCENTAGE);

            }

            $this->discountPercentage = $configuration->getConfig(self::CART_DISCOUNT_PERCENTAGE);

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
        return 'Cart Discount: Discount of ' . ($total->getAmount() / $total->getCurrency()->getSubUnit());
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
     * @return \SebastianBergmann\Money\Money
     */
    protected function getTotal()
    {
        if ($this->discountAmounts) {
            $currency = $this->currencyService->getActiveCurrency();
            $currencyCode = $currency->getCurrencyCode();
            
            if (isset($this->discountAmounts[$currencyCode])) {
                $discount = new Money(intval($this->discountAmounts[$currencyCode] * $currency->getSubUnit()), $currency);
            }
        }

        if ($this->discountPercentage) {
            $total = $this->purchasableHolder->getTotal();
            list($discount, ) = $total->allocateByRatios([$this->discountPercentage, 100 - $this->discountPercentage]);
        }

        if ($discount instanceof Money) {

            $purchasables = $this->purchasableHolder->getPurchasables();

            $total = $this->purchasableHolder->getTotal();

            foreach ($purchasables as $purchasable) {

                if ($purchasable instanceof DealPurchasableInterface) {

                    $discountRatio = $purchasable->getTotal()->getAmount() / $total->getAmount();

                    list($purchasableDiscount, ) = $discount->allocateByRatios([
                        $discountRatio,
                        1 - $discountRatio
                    ]);

                    $purchasable->setDealDiscount(
                        $this->getDealHandler()->getIdentifier(),
                        $purchasableDiscount
                    );
                }

            }

            return $discount;
        }
        
        return $this->currencyService->getZeroMoney();
    }
}
