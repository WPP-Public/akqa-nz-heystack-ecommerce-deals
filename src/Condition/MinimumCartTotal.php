<?php

namespace Heystack\Deals\Condition;

use Heystack\Core\Interfaces\HasEventServiceInterface;
use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Deals\Interfaces\ConditionAlmostMetInterface;
use Heystack\Deals\Interfaces\ConditionInterface;
use Heystack\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Deals\Interfaces\NonPurchasableInterface;
use Heystack\Deals\Traits\HasDealHandlerTrait;
use Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Ecommerce\Currency\Traits\HasCurrencyServiceTrait;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Purchasable\PurchasableHolder\Interfaces\HasPurchasableHolderInterface;
use Heystack\Purchasable\PurchasableHolder\Traits\HasPurchasableHolderTrait;
use SebastianBergmann\Money\Money;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package \Heystack\Deals\Condition
 */
class MinimumCartTotal
    implements
        ConditionInterface,
        ConditionAlmostMetInterface,
        HasDealHandlerInterface,
        HasPurchasableHolderInterface
{
    use HasDealHandlerTrait;
    use HasPurchasableHolderTrait;
    use HasCurrencyServiceTrait;

    const CONDITION_TYPE = 'MinimumCartTotal';
    const AMOUNTS_KEY = 'amounts';

    protected $amounts;

    /**
     * The amount condition determines whether the total in the product holder is greater than or equal to the configured threshold amount.
     * It takes into consideration the different currencies.
     *
     * @param \Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface $purchasableHolder
     * @param \Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface $currencyService
     * @param \Heystack\Deals\Interfaces\AdaptableConfigurationInterface $configuration
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
     * @return bool
     */
    public function met()
    {
        $currency = $this->currencyService->getActiveCurrency();
        $activeCurrencyCode = $currency->getCurrencyCode();
        
        if (isset($this->amounts[$activeCurrencyCode])) {
            $total = $this->dealHandler->getPurchasablesTotalWithDiscounts(
                $this->purchasableHolder->getPurchasables()
            );
            
            $amount = \Heystack\Ecommerce\convertStringToMoney($this->amounts[$activeCurrencyCode], $currency);

            return $total->greaterThanOrEqual($amount);
        }
        
        return false;
    }

    /**
     * @return bool|int
     */
    public function almostMet()
    {
        $purchasableHolder = $this->getPurchasableHolder();
        $met = false;

        if ($this->met()) {
            return $met;
        }

        if ($purchasableHolder instanceof HasEventServiceInterface) {
            $purchasableHolder->getEventService()->setEnabled(false);
        }

        foreach ($this->purchasableHolder->getPurchasables() as $purchasable) {

            // It is not relevant to test adding a non purchasable item to the cart,
            // because the user can never actually add it
            if (!$purchasable instanceof NonPurchasableInterface) {
                $quantity = $purchasable->getQuantity();

                $this->purchasableHolder->setPurchasable($purchasable, $quantity + 1);
                $this->purchasableHolder->updateTotal(false);
                $met = $this->met();
                $this->purchasableHolder->setPurchasable($purchasable, $quantity);
                $this->purchasableHolder->updateTotal(false);

                if ($met) {
                    break;
                }
            }

        }

        if ($purchasableHolder instanceof HasEventServiceInterface) {
            $purchasableHolder->getEventService()->setEnabled(true);
        }

        return $met;
    }

    /**
     * Returns a short string that describes what the condition does
     * @return string
     */
    public function getDescription()
    {

        $description = '';

        foreach ($this->amounts as $key => $amount) {

            $description .= $key . ' : ' . $amount;

        }

        return 'The Transaction sub total must be greater than or equal to -  ' . $description;
    }

    /**
     * @param mixed $amounts
     * @return void
     */
    public function setAmounts($amounts)
    {
        $this->amounts = $amounts;
    }

    /**
     * @return mixed
     */
    public function getAmounts()
    {
        return $this->amounts;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 100;
    }
}