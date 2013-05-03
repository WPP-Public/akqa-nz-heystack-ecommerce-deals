<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author     Glenn Bautista <glenn@heyday.co.nz>
 * @package    Ecommerce-Deals
 */
class FreePurchasable implements ResultInterface
{
    /**
     *
     */
    const IDENTIFIER = 'free_purchasables';

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
     * @var \Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface
     */
    protected $configuration;
    /**
     * @var int
     */
    protected $total = 0;

    /**
     * @param EventDispatcherInterface $eventService
     * @param PurchasableHolderInterface $purchasableHolder
     * @param State $stateService
     * @param AdaptableConfigurationInterface $configuration
     */
    public function __construct(
        EventDispatcherInterface $eventService,
        PurchasableHolderInterface $purchasableHolder,
        State $stateService,
        AdaptableConfigurationInterface $configuration
    ) {
        $this->eventService = $eventService;
        $this->purchasableHolder = $purchasableHolder;
        $this->stateService = $stateService;
        $this->configuration = $configuration;

        if (!$configuration->hasConfig('purchasable_identifier')) {
            throw new \Exception('Free Purchasables Result requires a purchasable_identifier configuration value');
        }
    }

    /**
     * @param Heystack\Subsystem\Core\Identifier\Identifier the deal handlers identifier
     */
    protected function saveState($identifier)
    {
        $this->stateService->setByKey(
            self::IDENTIFIER . $identifier,
            array(
                $this->total
            )
        );
    }

    /**
     * @param Heystack\Subsystem\Core\Identifier\Identifier the deal handler's identifier
     */
    protected function restoreState($identifier)
    {
        $data = $this->stateService->getByKey(self::IDENTIFIER . $identifier);
        if (is_array($data)) {
            list($this->processed, $this->total) = $data;
        }
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Free Purchasable:' . $this->configuration['purchasable_identifier'];
    }

    /**
     * @param DealHandlerInterface $dealHandler
     * @return mixed
     */
    public function process(DealHandlerInterface $dealHandler)
    {
        $dealIdentifier = $dealHandler->getIdentifier()->getFull();
        $this->restoreState($dealIdentifier);

        $purchasable = $this->getPurchasable();

        if (!$this->processed($purchasable)) {

            //Add the selected purchasable to the purchasableHolder
            $this->purchasableHolder->addPurchasable($purchasable);
            $this->total = $purchasable->getUnitPrice();
            $this->saveState($dealIdentifier);

            $this->eventService->dispatch(Events::RESULT_PROCESSED);
        }

        return $this->total;
    }

    protected function processed(PurchasableInterface $purchasable)
    {
        $productHolderPurchasable = $this->purchasableHolder->getPurchasable($purchasable->getIdentifier());

        return $productHolderPurchasable instanceof PurchasableInterface;
    }

    /**
     * @throws \Exception
     * @return \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface
     */
    protected function getPurchasable()
    {
        //Seaparate the ID from the ClassName in the Identifier
        preg_match('|^([a-z]+)([\d]+)$|i', $this->configuration->getConfig('purchasable_identifier'), $match);

        $purchasable = \DataObject::get_by_id($match[1], $match[2]);

        if (!$purchasable instanceof PurchasableInterface) {
            throw new \Exception('Purchasable on result free purchasable must an instanceof PurchaseableInterface');
        }
        return $purchasable;
    }
}
