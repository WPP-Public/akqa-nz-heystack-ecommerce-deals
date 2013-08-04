<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\HasPurchasableHolderInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Deals\Traits\HasPurchasableHolder;
use Heystack\Subsystem\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class CartDiscount implements ResultInterface, HasPurchasableHolderInterface
{
    use HasPurchasableHolder;

    const RESULT_TYPE = 'CartDiscount';
    const CART_DISCOUNT_AMOUNTS = 'cart_discount_amounts';
    const CART_DISCOUNT_PERCENTAGE = 'cart_discount_percentage';

    protected $discountAmounts;
    protected $discountPercentage;

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
            $discountConfigured = self::CART_DISCOUNT_PERCENTAGE;

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
        return 'Cart Discount: Discount of ' . $this->getTotal();
    }

    /**
     * Main function that determines what the result does
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $this->eventService->dispatch(Events::RESULT_PROCESSED);
        return $this->getTotal();
    }

    /**
     * @return mixed
     */
    protected function getTotal()
    {
        $total = $this->purchasableHolder->getTotal();

        if ($this->discountAmounts) {

            $currencyCode = $this->currencyService->getActiveCurrencyCode();

            return isset($this->discountAmounts[$currencyCode]) ? $this->discountAmounts[$currencyCode] : 0;

        }

        if ($this->discountPercentage) {

            return (($total / 100) * $this->discountPercentage);

        }
    }
}
