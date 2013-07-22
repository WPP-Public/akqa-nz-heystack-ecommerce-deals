<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\DataObjectHandler\DataObjectHandlerInterface;
use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Subsystem\Deals\Interfaces\PurchasableConditionInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author     Glenn Bautista <glenn@heyday.co.nz>
 * @package    Ecommerce-Deals
 */
class FreeGift implements ResultInterface
{
    const RESULT_TYPE = 'FreeGift';
    const PURCHASABLE_CLASS = 'purchasable_class';
    const PURCHASABLE_ID = 'purchasable_id';

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;
    /**
     * @var \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    protected $purchasableHolder;
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

        $quantity = $dealHandler->getConditionsRecursivelyMetCount();

        $purchasable = $this->getPurchasable();

        $processedQuantity = $purchasable->getFreeQuantity($dealIdentifier);

        if ($processedQuantity < $quantity) {

            $purchasable->setFreeQuantity($dealIdentifier, $quantity);

            $this->purchasableHolder->addPurchasable($purchasable, $quantity - $processedQuantity);

        }else if($processedQuantity > $quantity) {

            $purchasable->setFreeQuantity($dealIdentifier, $quantity);

            $subtract = $processedQuantity - $quantity;

            $this->purchasableHolder->setPurchasable($purchasable, $purchasable->getQuantity() - $subtract);

        }

        return $purchasable->getUnitPrice() * $quantity;
    }

    /**
     * Retrieve the purchasable either from the purchasable holder or the data store
     *
     * @return \Heystack\Subsystem\Core\DataObjectHandler\DataObject|DealPurchasableInterface
     */
    protected function getPurchasable()
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
