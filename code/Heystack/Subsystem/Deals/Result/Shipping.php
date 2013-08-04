<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Subsystem\Shipping\Interfaces\ShippingHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class Shipping implements ResultInterface
{
    const RESULT_TYPE = 'Shipping';
    const FREE_SHIPPING = 'free_shipping';
    const SHIPPING_DISCOUNT_AMOUNTS = 'shipping_discount_amounts';
    const SHIPPING_DISCOUNT_PERCENTAGE = 'shipping_discount_percentage';

    protected $isFree = false;
    protected $discountAmounts;
    protected $discountPercentage;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;
    /**
     * @var \Heystack\Subsystem\Shipping\Interfaces\ShippingHandlerInterface
     */
    protected $shippingService;
    /**
     * @var \Heystack\Subsystem\Ecommerce\Currency\Interfaces\CurrencyServiceInterface
     */
    protected $currencyService;

    /**
     * @param EventDispatcherInterface $eventService
     * @param ShippingHandlerInterface $shippingService
     * @param CurrencyServiceInterface $currencyService
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception when configured improperly
     */
    public function __construct(
        EventDispatcherInterface $eventService,
        ShippingHandlerInterface $shippingService,
        CurrencyServiceInterface $currencyService,
        AdaptableConfigurationInterface $configuration
    ) {
        $this->eventService = $eventService;
        $this->shippingService = $shippingService;
        $this->currencyService = $currencyService;

        $discountConfigured = false;

        if ($configuration->hasConfig(self::FREE_SHIPPING)) {

            $this->isFree = true;
            $discountConfigured = self::FREE_SHIPPING;

        }

        if ($configuration->hasConfig(self::SHIPPING_DISCOUNT_AMOUNTS)) {

            if ($discountConfigured) {

                throw new \Exception('Shipping Result requires that only one discount is configured. Please remove one of the following: ' . $discountConfigured . ',  ' . self::SHIPPING_DISCOUNT_AMOUNTS);

            }

            $discountAmounts = $configuration->getConfig(self::SHIPPING_DISCOUNT_AMOUNTS);

            if (is_array($discountAmounts) && count($discountAmounts)) {

                $this->discountAmounts = $discountAmounts;
                $discountConfigured = self::SHIPPING_DISCOUNT_AMOUNTS;

            } else {

                throw new \Exception('Shipping Result requires that the discount amounts are configured using an array of amounts with their indexes being the currency code.');

            }


        }

        if ($configuration->hasConfig(self::SHIPPING_DISCOUNT_PERCENTAGE)) {

            if ($discountConfigured) {

                throw new \Exception('Shipping Result requires that only one discount is configured. Please remove one of the following: ' . $discountConfigured . ',  ' . self::SHIPPING_DISCOUNT_AMOUNTS);

            }

            $this->discountPercentage = $configuration->getConfig(self::SHIPPING_DISCOUNT_PERCENTAGE);
            $discountConfigured = self::SHIPPING_DISCOUNT_PERCENTAGE;

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
        return 'Free shipping: Discount of ' . $this->getTotal();
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
        $total = $this->shippingService->getTotal();

        if ($this->isFree) {

            return $total;

        }

        if ($this->discountAmounts) {

            $currencyCode = $this->currencyService->getActiveCurrencyCode();

            return isset($this->discountAmounts[$currencyCode]) ? $this->discountAmounts[$currencyCode] : 0;

        }

        if ($this->discountPercentage) {

            return (($total / 100) * $this->discountPercentage);

        }
    }
}
