<?php

namespace Heystack\Subsystem\Deals\Condition;

use Heystack\Subsystem\Core\Interfaces\HasEventServiceInterface;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionAlmostMetInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;
use Heystack\Subsystem\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Subsystem\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\HasPurchasableHolderInterface;
use Heystack\Subsystem\Deals\Result\FreeGift;
use Heystack\Subsystem\Deals\Traits\HasDealHandler;
use Heystack\Subsystem\Deals\Traits\HasPurchasableHolder;
use Heystack\Subsystem\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package \Heystack\Subsystem\Deals\Condition
 */
class MinimumCartTotal implements ConditionInterface, ConditionAlmostMetInterface, HasDealHandlerInterface, HasPurchasableHolderInterface
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
        $quantity = 0;

        // iterate over products and discount the total by the free quantity * unit price
        foreach ($this->purchasableHolder->getPurchasables() as $purchasable) {

            $quantity += $purchasable->getQuantity();

            if ($purchasable instanceof DealPurchasableInterface) {

                if ($purchasable->hasFreeItems()){

                    $total -= $purchasable->getFreeQuantity() * $purchasable->getUnitPrice();

                }

            }

        }

        if (isset($this->amounts[$activeCurrencyCode]) && $total >= $this->amounts[$activeCurrencyCode]) {

            return true;

        }

        return false;
    }

    public function almostMet()
    {
        $purchasableHolder = $this->getPurchasableHolder();
        $met = false;

        if ($this->met()) {
            return $met;
        }

        if ($purchasableHolder instanceof HasEventServiceInterface) {
            $this->purchasableHolder->getEventService()->setEnabled(false);
        }

        foreach ($this->purchasableHolder->getPurchasables() as $purchasable) {

            // It is not relevant to test adding a non purchasable item to the cart,
            // because the user can never actually add it
            if (!$purchasable instanceof NonPurchasableInterface) {

                $quantity = $purchasable->getQuantity();

                $this->purchasableHolder->setPurchasable($purchasable, $quantity + 1);
                $this->purchasableHolder->updateTotal();
                $met = $this->met();
                $this->purchasableHolder->setPurchasable($purchasable, $quantity);
                $this->purchasableHolder->updateTotal();

                if ($met) {
                    break;
                }

            }

        }

        if ($purchasableHolder instanceof HasEventServiceInterface) {
            $this->purchasableHolder->getEventService()->setEnabled(true);
        }

        return $met;

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

    /**
     * @param mixed $amounts
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


}