<?php

namespace Heystack\Subsystem\Deals;

use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Core\State\StateableInterface;
use Heystack\Subsystem\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Subsystem\Core\Storage\StorableInterface;
use Heystack\Subsystem\Core\Storage\Traits\ParentReferenceTrait;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Ecommerce\Transaction\Traits\TransactionModifierSerializeTrait;
use Heystack\Subsystem\Ecommerce\Transaction\Traits\TransactionModifierStateTrait;
use Heystack\Subsystem\Ecommerce\Transaction\TransactionModifierTypes;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 *
 * @copyright  Heyday
 * @author     Glenn Bautista <glenn@heyday.co.nz>
 * @package    Ecommerce-Deals
 */
class DealHandler implements DealHandlerInterface, StateableInterface, \Serializable, StorableInterface
{
    use TransactionModifierStateTrait;
    use TransactionModifierSerializeTrait;
    use ParentReferenceTrait;

    /**
     * Identifier for state
     */
    const IDENTIFIER = 'deal_handler';
    /**
     * The total key for state
     */
    const TOTAL_KEY = 'total';
    /**
     * Stores data for state
     * @var array
     */
    protected $data = array();
    /**
     * The conditions that need to be met for the deal
     * @var array
     */
    protected $conditions = array();
    /**
     * The result of the deal if conditions are met
     * @var
     */
    protected $result;
    /**
     * @var \Heystack\Subsystem\Core\State\State
     */
    protected $stateService;
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    protected $eventService;
    /**
     * @var
     */
    protected $dealID;

    /**
     * @var
     */
    protected $promotionalMessage;

    /**
     * @param State $stateService
     * @param EventDispatcherInterface $eventService
     * @param                          $dealID
     */
    public function __construct(State $stateService, EventDispatcherInterface $eventService, $dealID, $promotionalMessage)
    {
        $this->stateService = $stateService;
        $this->eventService = $eventService;
        $this->dealID = $dealID;
        $this->promotionalMessage = $promotionalMessage;
    }

    /**
     * @return mixed
     */
    public function getPromotionalMessage()
    {
        return $this->promotionalMessage;
    }

    /**
     * @param $result
     */
    public function setResult($result)
    {
        $this->result = $result;
    }

    /**
     * @param $condition
     */
    public function addCondition($condition)
    {
        $this->conditions[] = $condition;
    }

    /**
     * Returns a unique identifier
     */
    public function getIdentifier()
    {
        return self::IDENTIFIER . $this->dealID;
    }

    /**
     * Returns the total value of the TransactionModifier for use in the Transaction
     */
    public function getTotal()
    {
        $this->restoreState();
        return isset($this->data[self::TOTAL_KEY]) ? $this->data[self::TOTAL_KEY] : 0;
    }

    /**
     * Update the total of the modifier
     */
    public function updateTotal()
    {
        $total = $this->conditionsMet() ? $this->result->process() : 0;

        $this->data[self::TOTAL_KEY] = $total;

        $this->saveState();

        $this->eventService->dispatch(Events::TOTAL_UPDATED);
    }


    /**
     * Checks if all the conditions are met
     * @param array $data Optional data array that will be passed onto the conditions for checking whether the conditions have been met.
     * @return bool
     */
    public function conditionsMet(Array $data = null)
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->met($data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Indicates the type of amount the modifier will return
     * Must return a constant from TransactionModifierTypes
     */
    public function getType()
    {
        return TransactionModifierTypes::DEDUCTIBLE;
    }

    /**
     * @return array
     */
    public function getStorableData()
    {
        return array(
            'id' => 'Tax',
            'parent' => true,
            'flat' => array(
                'Total' => $this->getTotal()
            )
        );
    }

    /**
     * @return string
     */
    public function getStorableIdentifier()
    {
        return self::IDENTIFIER;
    }

    /**
     * Get the name of the schema this system relates to
     * @return string
     */
    public function getSchemaName()
    {
        return 'Deal';

    }

    /**
     * @return array
     */
    public function getStorableBackendIdentifiers()
    {
        return array(
            Backend::IDENTIFIER
        );
    }
}
