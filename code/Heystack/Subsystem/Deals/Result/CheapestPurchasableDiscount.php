<?php
namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\AdaptableConfiguration;
use Heystack\Subsystem\Deals\Condition;
use Heystack\Subsystem\Deals\Events\ConditionEvent;
use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Deals\Events\ResultEvent;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Subsystem\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\HasPurchasableHolderInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultWithConditionsInterface;
use Heystack\Subsystem\Deals\Traits\HasDealHandler;
use Heystack\Subsystem\Deals\Traits\HasPurchasableHolder;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CheapestPurchasableDiscount implements ResultInterface, ResultWithConditionsInterface, HasPurchasableHolderInterface, HasDealHandlerInterface
{
    use HasDealHandler;
    use HasPurchasableHolder;

    const RESULT_TYPE = 'CheapestPurchasableDiscount';
    const PURCHASABLE_IDENTIFIER_STRINGS = 'purchasable_identifier_strings';

    /**
     * Constants used internally
     */
    const PURCHASABLE_KEY = 'purchasable';
    const QUANTITY_KEY = 'quantity';

    protected $purchasableIdentifiers = [];

    protected $totalDiscount = 0;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;

    public function __construct(
        EventDispatcherInterface $eventService,
        PurchasableHolderInterface $purchasableHolder,
        AdaptableConfigurationInterface $configuration
    ) {
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
        return 'Cheapest Purchasable Discount: Discount of ' . $this->totalDiscount;
    }

    /**
     * Main function that determines what the result does
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        /**
         * Reset the free count for this deal of all the purchasables
         */
        $purchasables = $this->getPurchasableHolder()->getPurchasables();

        if(is_array($purchasables) && count($purchasables)){

            foreach($purchasables as $purchasable){

                if($purchasable instanceof DealPurchasableInterface){

                    $purchasable->setFreeQuantity($dealHandler->getIdentifier(), 0);

                }
            }

        }


        $count = $dealHandler->getConditionsMetCount();

        $actionablePurchasables = $this->getActionablePurchasables();

        $cheapestCount = [];

        for ($i = 0; $i < $count; $i++) {

            $cheapest = $this->getCheapest($actionablePurchasables);

            if($cheapest){

                $fullIdentifierString = $cheapest->getIdentifier()->getFull();

                if(!isset($cheapestCount[$fullIdentifierString])){

                    $cheapestCount[$fullIdentifierString] = [
                        'purchasable' => $cheapest,
                        'count' => 1
                    ];

                }else{

                    $cheapestCount[$fullIdentifierString]['count']++;

                }

            }

        }

        foreach($cheapestCount as $countData){

            $purchasable = $countData['purchasable'];

            $freeQuantity = $purchasable->getFreeQuantity($dealHandler->getIdentifier());

            if($freeQuantity != $countData['count'] && (count($this->purchasableHolder->getPurchasables()) > 1 ||  $purchasable->getQuantity() != 1)){

                $purchasable->setFreeQuantity($dealHandler->getIdentifier(), $countData['count']);

            }

            $this->totalDiscount += $purchasable->getUnitPrice() * $countData['count'];

        }

        $this->eventService->dispatch(Events::RESULT_PROCESSED, new ResultEvent($this));

        return $this->totalDiscount;
    }

    public function onConditionsNotMet(ConditionEvent $event)
    {
        $dealIdentifier = $this->getDealHandler()->getIdentifier();

        if ($dealIdentifier->isMatch($event->getDeal()->getIdentifier())) {

            if (($result = $this->dealHandler->getResult()) instanceof CheapestPurchasableDiscount) {

                foreach ($this->getPurchasables() as $purchasable) {

                    $purchasable->setFreeQuantity($dealIdentifier, 0);

                }

            }

        }

        // TODO: Does this need to do this?
        $this->purchasableHolder->saveState();
    }

    /**
     * @param array $actionablePurchasables
     * @return bool|DealPurchasableInterface|PurchasableInterface
     */
    protected function getCheapest(array &$actionablePurchasables)
    {
        $cheapest = false;

        foreach($actionablePurchasables as $purchasableData){

            /**
             * @var DealPurchasableInterface $purchasable
             */
            $purchasable = $purchasableData[self::PURCHASABLE_KEY];
            $quantity = $purchasableData[self::QUANTITY_KEY];

            if(!$cheapest && $quantity) {

                $cheapest = $purchasable;

            }else if($cheapest instanceof PurchasableInterface && $cheapest->getPrice() > $purchasable->getPrice() && $quantity) {

                $cheapest = $purchasable;

            }

        }

        if($cheapest){

            $actionablePurchasables[$cheapest->getIdentifier()->getFull()][self::QUANTITY_KEY] -= 1;

        }


        return $cheapest;
    }

    protected function getActionablePurchasables()
    {
        $actionablePurchasables = [];
        $purchasables = $this->getPurchasables();

        foreach($purchasables as $purchasable){

            $actionablePurchasables[$purchasable->getIdentifier()->getFull()] = [
                self::PURCHASABLE_KEY => $purchasable,
                self::QUANTITY_KEY => $purchasable->getQuantity()
            ];

        }

        return $actionablePurchasables;
    }

    protected function getPurchasables()
    {
        $purchasables = [];

        if (count($this->purchasableIdentifiers)) {

            foreach ($this->purchasableIdentifiers as $purchasableIdentifier) {

                $purchasableHolderPurchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier($purchasableIdentifier);

                if(is_array($purchasableHolderPurchasables)){

                    $purchasables = array_merge(
                        $purchasables,
                        $purchasableHolderPurchasables
                    );

                }

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

        $purchasableInCartCondition = new Condition\PurchasableHasQuantityInCart($this->getPurchasableHolder(), new AdaptableConfiguration($productConfig));

        return [
            $purchasableInCartCondition
        ];

    }


}

