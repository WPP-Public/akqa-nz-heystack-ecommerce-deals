<?php

namespace Heystack\Deals;

use Heystack\Core\EventDispatcher;
use Heystack\Core\Identifier\Identifier;
use Heystack\Core\State\State;
use Heystack\Core\State\StateableInterface;
use Heystack\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Core\Storage\Traits\ParentReferenceTrait;
use Heystack\Core\Traits\HasEventServiceTrait;
use Heystack\Core\Traits\HasStateServiceTrait;
use Heystack\Deals\Events\ConditionEvent;
use Heystack\Deals\Events\TotalUpdatedEvent;
use Heystack\Deals\Interfaces\ConditionAlmostMetInterface;
use Heystack\Deals\Interfaces\ConditionInterface;
use Heystack\Deals\Interfaces\DealHandlerInterface;
use Heystack\Deals\Interfaces\DealPurchasableInterface;
use Heystack\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Deals\Interfaces\HasPriorityInterface;
use Heystack\Deals\Interfaces\ResultInterface;
use Heystack\Deals\Interfaces\ResultWithConditionsInterface;
use Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface;
use Heystack\Ecommerce\Currency\Traits\HasCurrencyServiceTrait;
use Heystack\Ecommerce\Transaction\Interfaces\HasLinkedTransactionModifiersInterface;
use Heystack\Ecommerce\Transaction\Traits\TransactionModifierSerializeTrait;
use Heystack\Ecommerce\Transaction\Traits\TransactionModifierStateTrait;
use SebastianBergmann\Money\Money;

/**
 *
 * @copyright  Heyday
 * @author     Glenn Bautista <glenn@heyday.co.nz>
 * @package    Ecommerce-Deals
 */
class DealHandler implements
    DealHandlerInterface,
    HasLinkedTransactionModifiersInterface,
    StateableInterface,
    \Serializable
{
    use TransactionModifierStateTrait;
    use TransactionModifierSerializeTrait;
    use ParentReferenceTrait;
    use HasCurrencyServiceTrait;
    use HasEventServiceTrait;
    use HasStateServiceTrait;

    /**
     * Identifier for state
     */
    const IDENTIFIER = 'deal';
    /**
     * The conditions that need to be met for the deal
     * @var \Heystack\Deals\Interfaces\ConditionInterface[]
     */
    protected $conditions = [];
    /**
     * The result of the deal if conditions are met
     * @var \Heystack\Deals\Interfaces\ResultInterface
     */
    protected $result;
    /**
     * @var \SebastianBergmann\Money\Money
     */
    protected $total;
    /**
     * @var string
     */
    protected $dealID;
    /**
     * @var int the number of times the conditions were met more than once
     */
    protected $conditionsMetCount = 0;
    /**
     * @var string
     */
    protected $promotionalMessage;

    /**
     * @param \Heystack\Core\State\State $stateService
     * @param \Heystack\Core\EventDispatcher $eventService
     * @param \Heystack\Ecommerce\Currency\Interfaces\CurrencyServiceInterface $currencyService
     * @param string $dealID
     * @param string $promotionalMessage
     */
    public function __construct(
        State $stateService,
        EventDispatcher $eventService,
        CurrencyServiceInterface $currencyService,
        $dealID,
        $promotionalMessage
    ) {
        $this->stateService = $stateService;
        $this->eventService = $eventService;
        $this->currencyService = $currencyService;
        $this->dealID = $dealID;
        $this->promotionalMessage = @unserialize($promotionalMessage); //TODO: This has to be changed.
        
        $this->total = $this->currencyService->getZeroMoney();

        $this->restoreState();
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function getPromotionalMessage($type)
    {
        return isset($this->promotionalMessage[$type]) ? $this->promotionalMessage[$type] : null;
    }

    /**
     * @param \Heystack\Deals\Interfaces\ResultInterface $result
     * @return void
     */
    public function setResult(ResultInterface $result)
    {
        $this->result = $result;

        if ($this->result instanceof ResultWithConditionsInterface) {

            foreach ($this->result->getConditions() as $condition) {

                $this->addCondition($condition);

            }

        }

        if ($result instanceof HasDealHandlerInterface) {

            $result->setDealHandler($this);

        }
    }

    /**
     * @param \Heystack\Deals\Interfaces\ConditionInterface $condition
     * @return void
     */
    public function addCondition(ConditionInterface $condition)
    {
        $this->conditions[$condition->getType()] = $condition;

        if ($condition instanceof HasDealHandlerInterface) {

            $condition->setDealHandler($this);

        }
    }

    /**
     * Returns a unique identifier
     * @return \Heystack\Core\Identifier\Identifier
     */
    public function getIdentifier()
    {
        return new Identifier(self::IDENTIFIER . $this->dealID);
    }

    /**
     * Returns the total value of the TransactionModifier for use in the Transaction
     * @return \SebastianBergmann\Money\Money
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Update the total of the modifier
     * @return void
     */
    public function updateTotal()
    {
        if ($this->conditionsMet() && $this->result instanceof ResultInterface) {
            $total = $this->result->process($this);
        } else {
            $total = $this->currencyService->getZeroMoney();
        }
        
        $this->total = $total;

        $this->saveState();

        $this->eventService->dispatch(
            Events::TOTAL_UPDATED,
            new TotalUpdatedEvent($this)
        );
    }

    /**
     * Checks if all the conditions are met.
     *
     * If not all conditions are met and the $data array was null, this will dispatch the Conditions not met event.
     *
     * @param bool|void $dispatchEvents
     * @return bool
     */
    public function conditionsMet($dispatchEvents = true)
    {
        $met = [];

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
     * @return string
     */
    public function getType()
    {
        return $this->result->getType();
    }

    /**
     * @return \Heystack\Ecommerce\Transaction\Interfaces\TransactionModifierInterface[]
     */
    public function getLinkedModifiers()
    {
        return $this->result->getLinkedModifiers();
    }

    /**
     * @return \Heystack\Deals\Interfaces\ConditionInterface[]
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

    /**
     * @return \Heystack\Deals\Interfaces\ResultInterface
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Returns whether the deal is almost completed based on the conditions it has
     * @return boolean
     */
    public function almostMet()
    {
        $almostMet = true;

        foreach ($this->getConditions() as $condition) {

            if ($condition instanceof ConditionAlmostMetInterface) {

                if (!$condition->almostMet()) {
                    $almostMet = false;
                    break;
                }

            }

        }

        return $almostMet;
    }

    /**
     * @return array
     */
    public function getStorableData()
    {
        //loop of conditions, use result to prepare a combined description
        return [
            'id' => 'Deal',
            'parent' => true,
            'flat' => [
                'Total' => \Heystack\Ecommerce\convertMoneyToString($this->total),
                'Description' => $this->getDescription(),
                'ParentID' => $this->parentReference
            ]
        ];
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
        return [
            Backend::IDENTIFIER
        ];
    }

    /**
     * Helper function for getting the state of the deal handler
     * @return string
     */
    protected function getDescription()
    {
        $conditionDescriptions = [];
        foreach ($this->conditions as $condition) {
            $conditionDescriptions[] = $condition->getDescription();
        }
        $conditionDescription = implode(PHP_EOL, $conditionDescriptions);
        $resultDescription = $this->result instanceof ResultInterface ? $this->result->getDescription() : 'No Result';
        return <<<DESCRIPTION
Conditions:
$conditionDescription
Result:
$resultDescription
DESCRIPTION;
    }

    /**
     * @param mixed $data
     * @return void
     */
    public function setData($data)
    {
        if (is_array($data)) {
            list($this->total, $this->conditionsMetCount) = $data;
        }
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return [$this->total, $this->conditionsMetCount];
    }

    /**
     * @param \Heystack\Ecommerce\Purchasable\Interfaces\PurchasableInterface[] $purchasbles
     * @return \SebastianBergmann\Money\Money
     */
    public function getPurchasablesTotalWithDiscounts(array $purchasbles)
    {
        $dealIdentifier = $this->getIdentifier()->getFull();

        $parts = [];

        foreach ($purchasbles as $purchaseable) {
            if ($purchaseable instanceof DealPurchasableInterface) {
                $parts[] = $purchaseable->getTotal()->subtract($purchaseable->getDealDiscountWithExclusions([$dealIdentifier]));
            } else {
                $parts[] = $purchaseable->getTotal();
            }
        }

        return array_reduce($parts, function (Money $total, Money $item) {
            return $total->add($item);
        }, $this->currencyService->getZeroMoney());
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        $priorities = [$this->result->getPriority()];
        
        foreach ($this->conditions as $condition) {
            $priorities[] = $condition->getPriority();
        }

        return call_user_func_array('max', $priorities);
    }

    /**
     * Return 0 for equal, -1 for this less than other and 1 for this greater than other
     * @param \Heystack\Deals\Interfaces\HasPriorityInterface $other
     * @return int
     */
    public function compareTo(HasPriorityInterface $other)
    {
        $resultPriority = $this->getPriority();
        $otherResultPriority = $other->getPriority();

        if ($resultPriority === $otherResultPriority) {
            return 0;
        }
        
        return $resultPriority < $otherResultPriority ? -1 : 1;
    }
}
