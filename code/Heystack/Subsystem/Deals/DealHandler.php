<?php

namespace Heystack\Subsystem\Deals;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Core\State\StateableInterface;
use Heystack\Subsystem\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Subsystem\Core\Storage\StorableInterface;
use Heystack\Subsystem\Core\Storage\Traits\ParentReferenceTrait;
use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;
use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
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
    const IDENTIFIER = 'deal';
    /**
     * The total key for state
     */
    const TOTAL_KEY = 'total';
    /**
     * The conditions that need to be met for the deal
     * @var \Heystack\Subsystem\Deals\Interfaces\ConditionInterface[]
     */
    protected $conditions = array();
    /**
     * The result of the deal if conditions are met
     * @var \Heystack\Subsystem\Deals\Interfaces\ResultInterface
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
     * @var int the number of times the conditions were met more than once
     */
    protected $conditionsMetCount = 0;
    /**
     * @var
     */
    protected $promotionalMessage;

    /**
     * @param State $stateService
     * @param EventDispatcherInterface $eventService
     * @param                          $dealID
     * @param                          $promotionalMessage
     */
    public function __construct(
        State $stateService,
        EventDispatcherInterface $eventService,
        $dealID,
        $promotionalMessage
    ) {
        $this->stateService = $stateService;
        $this->eventService = $eventService;
        $this->dealID = $dealID;
        $this->promotionalMessage = @unserialize($promotionalMessage);

        $this->restoreState();
    }

    /**
     * @param $type
     * @return mixed
     */
    public function getPromotionalMessage($type)
    {
        return isset($this->promotionalMessage[$type]) ? $this->promotionalMessage[$type] : null;
    }

    /**
     * @param \Heystack\Subsystem\Deals\Interfaces\ResultInterface $result
     * @return mixed|void
     */
    public function setResult(ResultInterface $result)
    {
        $this->result = $result;

        if (method_exists($result, 'setDealHandler')) {

            $result->setDealHandler($this);

        }
    }

    /**
     * @param \Heystack\Subsystem\Deals\Interfaces\ConditionInterface $condition
     * @return mixed|void
     */
    public function addCondition(ConditionInterface $condition)
    {
        $this->conditions[$condition->getType()] = $condition;

        if (method_exists($condition, 'setDealHandler')) {

            $condition->setDealHandler($this);

        }
    }

    /**
     * Returns a unique identifier
     * @return \Heystack\Subsystem\Core\Identifier\Identifier
     */
    public function getIdentifier()
    {
        return new Identifier(self::IDENTIFIER . $this->dealID);
    }

    /**
     * Returns the total value of the TransactionModifier for use in the Transaction
     */
    public function getTotal()
    {
        return isset($this->data[self::TOTAL_KEY]) ? $this->data[self::TOTAL_KEY] : 0;
    }

    /**
     * Update the total of the modifier
     */
    public function updateTotal()
    {
        $total = $this->conditionsMet() ? $this->result->process($this) : 0;

        $this->data[self::TOTAL_KEY] = $total;

        $this->saveState();

        $this->eventService->dispatch(Events::TOTAL_UPDATED);
    }

    /**
     * Checks if all the conditions are met.
     *
     * If not all conditions are met and the $data array was null, this will dispatch the Conditions not met event.
     *
     * The data array is generally used to check if the condition will be met given a certain set of data. (useful on the front end)
     *
     * @param bool $dispatchEvents
     * @return bool
     */
    public function conditionsMet($dispatchEvents = true)
    {
        $met = array();

        foreach ($this->conditions as $condition) {

            $metCount = $condition->met();

            if ($metCount) {

                if (is_int($metCount)) {

                    $met[] = $metCount;

                } else {

                    $met[] = 1;

                }

            } else {

                $met = false;

                break;

            }

        }

        if ($met) {
            $this->conditionsMetCount = max($met);
        }

        if ($dispatchEvents) {
            if ($met !== false) {
                $this->eventService->dispatch(Events::CONDITIONS_MET, new ConditionEvent($this));
            } else {
                $this->eventService->dispatch(Events::CONDITIONS_NOT_MET, new ConditionEvent($this));
            }
        }

        return $met !== false;
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
        //loop of conditions, use result to prepare a combined description
        return array(
            'id' => 'Deal',
            'parent' => true,
            'flat' => array(
                'Total' => $this->getTotal(),
                'Description' => $this->getDescription(),
                'ParentID' => $this->parentReference
            )
        );
    }

    /**
     * @return string
     */
    protected function getDescription()
    {
        $conditionDescriptions = array();
        foreach ($this->conditions as $condition) {
            $conditionDescriptions[] = $condition->getDescription();
        }
        $conditionDescription = implode(PHP_EOL, $conditionDescriptions);
        $resultDescription = $this->result->getDescription();
        return <<<DESCRIPTION
Conditions:
$conditionDescription
Result:
$resultDescription
DESCRIPTION;
    }

    /**
     * @return string
     */
    public function getStorableIdentifier()
    {
        return self::IDENTIFIER . $this->dealID;
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

    /**
     * @return array of conditions
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Returns the number of times that each condition was met more than once
     * @return int
     */
    public function getConditionsMetCount()
    {
        return $this->conditionsMetCount;
    }

    public function getResult()
    {
        return $this->result;
    }

}
