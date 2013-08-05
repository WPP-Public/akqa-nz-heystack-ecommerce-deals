<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\DataObjectHandler\DataObjectHandlerInterface;
use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\Interfaces\HasEventServiceInterface;
use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Deals\ConditionEvent;
use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Subsystem\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\HasPurchasableHolderInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Deals\Traits\HasDealHandler;
use Heystack\Subsystem\Deals\Traits\HasPurchasableHolder;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
            Events::CONDITIONS_MET     => 'onConditionsMet'
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
        $dealIdentifier = $dealHandler->getIdentifier();
        $conditionsMetCount = $dealHandler->getConditionsMetCount();
        $purchasable = $this->getPurchasable();

        if ($purchasable) {
            if ($purchasable->getQuantity() == 1 && count($this->getPurchasableHolder()->getPurchasables()) == 1) {

                // make sure there is an appropriate number of the product in the cart
                $this->purchasableHolder->setPurchasable(
                    $purchasable,
                    $purchasable->getQuantity() + 1
                );

            } else {

                // make sure there is an appropriate number of the product in the cart
                $this->purchasableHolder->setPurchasable(
                    $purchasable,
                    max( // use max to ensure there are at least enough in the cart to meet the conditionsMet requirement
                        $purchasable->getQuantity(),
                        $conditionsMetCount
                    )
                );

            }

            $purchasable->setFreeQuantity($dealIdentifier, $conditionsMetCount);

            return $purchasable->getUnitPrice() * $conditionsMetCount;
        }

        return 0;

    }

    public function onConditionsMet(ConditionEvent $event)
    {

    }

    public function onConditionsNotMet(ConditionEvent $event)
    {
        $dealIdentifier = $this->getDealHandler()->getIdentifier();

        if ($dealIdentifier->isMatch($event->getDeal()->getIdentifier())) {

            if (($result = $this->dealHandler->getResult()) instanceof FreeGift) {

                if ($result->getPurchasable()) {

                    $result->getPurchasable()->setFreeQuantity($dealIdentifier, 0);

                }

            }

        }

        // TODO: Does this need to do this?
        $this->purchasableHolder->saveState();
    }

    /**
     * Retrieve the purchasable either from the purchasable holder or the data store
     *
     * @return \Heystack\Subsystem\Core\DataObjectHandler\DataObject|DealPurchasableInterface
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
