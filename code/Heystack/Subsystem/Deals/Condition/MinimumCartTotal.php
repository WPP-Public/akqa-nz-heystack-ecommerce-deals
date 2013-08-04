<?php

namespace Heystack\Subsystem\Deals\Condition;


use Heystack\Subsystem\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\HasPurchasableHolderInterface;
use Heystack\Subsystem\Deals\Traits\HasDealHandler;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;
use Heystack\Subsystem\Deals\Result\FreeGift;
use Heystack\Subsystem\Deals\Traits\HasPurchasableHolder;
use Heystack\Subsystem\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class MinimumCartTotal implements ConditionInterface, HasDealHandlerInterface, HasPurchasableHolderInterface
{
    use HasDealHandler;
    use HasPurchasableHolder;

    const CONDITION_TYPE = 'MinimumCartTotal';
    const AMOUNTS_KEY = 'amounts';

    protected $amounts;

    /**
     * @var \Heystack\Subsystem\Ecommerce\Currency\Interfaces\CurrencyServiceInterface
     */
    protected $currencyService;

    /**
     * The amount condition determines whether the total in the product holder is greater than or equal to the configured threshold amount.
     * It takes into consideration the different currencies.
     *
     * @param PurchasableHolderInterface $purchasableHolder
     * @param CurrencyServiceInterface $currencyService
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception if the configuration does not have a configuration identifier
     */
    public function __construct(
        PurchasableHolderInterface $purchasableHolder,
        CurrencyServiceInterface $currencyService,
        AdaptableConfigurationInterface $configuration
    ) {
        if ($configuration->hasConfig(self::AMOUNTS_KEY)) {

            $this->amounts = $configuration->getConfig(self::AMOUNTS_KEY);

        } else {

            throw new \Exception('Minimum Cart Total Condition needs to be configured with all the amounts in the different currencies');

        }

        $this->purchasableHolder = $purchasableHolder;

        $this->currencyService = $currencyService;

    }

    /**
     * @return string that indicates the type of condition this class is implementing
     */
    public function getType()
    {
        return self::CONDITION_TYPE;
    }

    /**
     * Return a boolean indicating whether the condition has been met
     *
     * @return int
     */
    public function met()
    {
        $activeCurrencyCode = $this->currencyService->getActiveCurrencyCode();

        $total = $this->purchasableHolder->getTotal();

        $discountedPurchasables = array();

        $purchasables = $this->purchasableHolder->getPurchasables();

        $totalPurchasables = 0;

        foreach ($purchasables as $purchasable){

            $totalPurchasables += $purchasable->getQuantity();

            if ($purchasable->hasFreeItems()){

                $discountedPurchasables[] = $purchasable;

            }

        }

        // don't discount the purchasable price if its the only one in the cart
        if (count($discountedPurchasables) && $totalPurchasables > 1) {

            foreach($discountedPurchasables as $purchasable) {

                $total -= $purchasable->getFreeQuantity() * $purchasable->getUnitPrice();

            }

        }

        if (isset($this->amounts[$activeCurrencyCode]) && $total >= $this->amounts[$activeCurrencyCode]) {

            return true;

        }

        return false;
    }

    /**
     * Returns a short string that describes what the condition does
     */
    public function getDescription()
    {

        $description = '';

        foreach ($this->amounts as $key => $amount) {

            $description .= $key . ' : ' . $amount;

        }

        return 'The Transaction sub total must be greater than or equal to -  ' . $description;
    }

}