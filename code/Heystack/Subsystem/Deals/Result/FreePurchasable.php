<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Deals\Events;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Deals\Traits\ResultTrait;
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
    use ResultTrait;
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
     * @var
     */
    protected $purchaseable;
    /**
     * @var bool
     */
    protected $processed = false;
    /**
     * @var int
     */
    protected $total = 0;
    /**
     * @param EventDispatcherInterface        $eventService
     * @param PurchasableHolderInterface      $purchasableHolder
     * @param State                           $stateService
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception
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
     *
     */
    protected function saveState()
    {
        $this->stateService->setByKey(
            self::IDENTIFIER . $this->dealIdentifier,
            array(
                $this->processed,
                $this->total
            )
        );
    }
    /**
     *
     */
    protected function restoreState()
    {
        $data = $this->stateService->getByKey(self::IDENTIFIER . $this->dealIdentifier);
        if (is_array($data)) {
            list($this->processed, $this->total) = $data;
        }
    }
    /**
     * @return string
     */
    public function description()
    {
        return 'The product (' . $this->getPurchaseable()->getIdentifier()->getFull() . ') is now priced at ' . $this->value;
    }

    /**
     * @return mixed
     */
    public function process()
    {
        $this->restoreState();

        if (!$this->processed) {

            //Add the selected purchasable to the purchasableHolder
            $this->purchasableHolder->addPurchasable($purchasable = $this->getPurchaseable());
            $this->processed = true;
            $this->total = $purchasable->getPrice();
            $this->saveState();
            $this->eventService->dispatch(Events::RESULT_PROCESSED);

        }

        return $this->total;
    }
    /**
     * @throws \Exception
     * @return \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface
     */
    protected function getPurchaseable()
    {
        if (!$this->purchasable) {
            //Seaparate the ID from the ClassName in the Identifier
            preg_match('|^([a-z]+)([\d]+)$|i', $this->configuration->getConfig('purchasable_identifier'), $match);

            $this->purchasable = \DataObject::get_by_id($match[1], $match[2]);

            if (!$this->purchaseable instanceof PurchasableInterface) {
                throw new \Exception('Purchasable on result free purchasable must an instanceof PurchaseableInterface');
            }
        }
        return $this->purchasable;
    }
}
