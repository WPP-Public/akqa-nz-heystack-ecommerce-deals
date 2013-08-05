<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\DataObjectHandler\DataObjectHandlerInterface;
use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Deals\Events\ConditionEvent;
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
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Subsystem\Products\ProductHolder\Events as ProductEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author     Glenn Bautista <glenn@heyday.co.nz>
 * @package    Ecommerce-Deals
 */
class FreeGift implements ResultInterface, HasDealHandlerInterface, HasPurchasableHolderInterface
{
    use HasDealHandler;
    use HasPurchasableHolder;

    const RESULT_TYPE = 'FreeGift';
    const PURCHASABLE_CLASS = 'purchasable_class';
    const PURCHASABLE_ID = 'purchasable_id';

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;
    /**
     * @var \Heystack\Subsystem\Core\State\State
     */
    protected $stateService;
    /**
     * @var \Heystack\Subsystem\Core\DataObjectHandler\DataObjectHandlerInterface
     */
    protected $dataObjectHandler;
    /**
     * @var string
     */
    protected $purchasableClass;
    /**
     * @var int
     */
    protected $purchasableID;

    /**
     * @param EventDispatcherInterface $eventService
     * @param PurchasableHolderInterface $purchasableHolder
     * @param DataObjectHandlerInterface $dataObjectHandler
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception if the configuration is incorrect
     */
    public function __construct(
        EventDispatcherInterface $eventService,
        PurchasableHolderInterface $purchasableHolder,
        DataObjectHandlerInterface $dataObjectHandler,
        AdaptableConfigurationInterface $configuration
    ) {
        $this->eventService = $eventService;
        $this->purchasableHolder = $purchasableHolder;
        $this->dataObjectHandler = $dataObjectHandler;

        if ($configuration->hasConfig(self::PURCHASABLE_CLASS)) {

            $this->purchasableClass = $configuration->getConfig(self::PURCHASABLE_CLASS);

        } else {

            throw new \Exception('Free Gift Result requires a purchasable_class configuration value');

        }

        if ($configuration->hasConfig(self::PURCHASABLE_ID)) {

            $this->purchasableID = $configuration->getConfig(self::PURCHASABLE_ID);

        } else {

            throw new \Exception('Free Gift Result requires a purchasable_id configuration value');

        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            Events::CONDITIONS_NOT_MET => 'onConditionsNotMet',
            Events::CONDITIONS_MET     => 'onConditionsMet',
            ProductEvents::PURCHASABLE_REMOVED => array('onProductRemove', 100)
        );
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Free Purchasable: ' . $this->purchasableClass . $this->purchasableID;
    }

    /**
     * @param DealHandlerInterface $dealHandler
     * @return mixed
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $total = $this->getPurchasable()->getUnitPrice() * $dealHandler->getConditionsMetCount();
        $this->eventService->dispatch(Events::RESULT_PROCESSED, new ResultEvent($this));
        return $total;
    }

    /**
     * Applies the free gift to the purchasable holder.
     *
     * The event dispatcher is disabled here to not cause recursion as the free gift is added to the cart. In normal
     * circumstances adding a purchasable to the purschasable holder causes deal conditions to be re-evaluated, and
     * results of those conditions applied. Obviously in this situation, adding a free gift to the cart may cause those
     * events to fire causing other purchasables to be added as a result - leading to a bad situation
     *
     * There are two cases where the free gift must be added the purchasable holder
     *
     *  1) when the purchasable has already been added to the cart, but none are yet free.
     *  2) when the purchasable has already been added to the cart but the purchasables free quantity is less than the
     *     current amount of times this condition has been met.
     *
     * @param ConditionEvent $event
     */
    public function onConditionsMet(ConditionEvent $event)
    {
        // Should we get the event dispatcher off the event?
        $deal = $this->getDealHandler();
        $dealIdentifier = $deal->getIdentifier();
        $conditionsMetCount = $deal->getConditionsMetCount();

        // Only do stuff if it is relevant to this deal
        if ($dealIdentifier->isMatch($event->getDeal()->getIdentifier())) {

            $event->getDispatcher()->setEnabled(false);

            $purchasable = $this->getPurchasable();
            $purchasableAlreadyInCart = $this->purchasableHolder->getPurchasable($purchasable->getIdentifier());

            if ($purchasableAlreadyInCart instanceof DealPurchasableInterface) {

                if ($purchasableAlreadyInCart->getFreeQuantity() === 0) {

                    $this->purchasableHolder->addPurchasable($purchasableAlreadyInCart);

                } else if ($purchasableAlreadyInCart->getFreeQuantity() < $deal->getConditionsMetCount()) {

                    $this->purchasableHolder->addPurchasable($purchasableAlreadyInCart);

                }

            } else {

                // make sure there is an appropriate number of the product in the cart
                $this->purchasableHolder->setPurchasable(
                    $purchasable,
                    max(
                        $purchasable->getQuantity(),
                        $conditionsMetCount
                    )
                );
            }

            $purchasable->setFreeQuantity($dealIdentifier, $conditionsMetCount);

            $event->getDispatcher()->setEnabled(true);
        }
    }

    /**
     * When products are removed we need to set free quantities to 0
     * This will handle the
     */
    public function onProductRemove()
    {
        foreach ($this->purchasableHolder->getPurchasables() as $purchasable) {
            if ($purchasable instanceof DealPurchasableInterface) {
                $purchasable->setFreeQuantity($this->getDealHandler()->getIdentifier(), 0);
            }
        }
    }

    public function onConditionsNotMet(ConditionEvent $event)
    {
        $dealIdentifier = $this->getDealHandler()->getIdentifier();

        if ($dealIdentifier->isMatch($event->getDeal()->getIdentifier())) {
            $this->getPurchasable()->setFreeQuantity($dealIdentifier, 0);
        }

        $this->purchasableHolder->saveState();
    }

    /**
     * Retrieve the purchasable either from the purchasable holder or the data store
     *
     * @return \Heystack\Subsystem\Deals\Interfaces\DealPurchasableInterface
     */
    public function getPurchasable()
    {
        $purchasable = $this->purchasableHolder->getPurchasable(
            new Identifier($this->purchasableClass . $this->purchasableID)
        );

        if (!$purchasable instanceof DealPurchasableInterface) {

            $purchasable = $this->dataObjectHandler->getDataObjectById($this->purchasableClass, $this->purchasableID);

        }

        return $purchasable;
    }
}
