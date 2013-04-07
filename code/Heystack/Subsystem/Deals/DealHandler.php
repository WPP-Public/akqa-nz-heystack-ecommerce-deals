<?php

namespace Heystack\Subsystem\Deals;

use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Core\State\StateableInterface;

use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;

use Heystack\Subsystem\Ecommerce\Transaction\TransactionModifierTypes;
use Heystack\Subsystem\Ecommerce\Transaction\Traits\TransactionModifierStateTrait;
use Heystack\Subsystem\Ecommerce\Transaction\Traits\TransactionModifierSerializeTrait;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Heystack\Subsystem\Core\Storage\StorableInterface;
use Heystack\Subsystem\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Subsystem\Core\Storage\Traits\ParentReferenceTrait;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class DealHandler implements DealHandlerInterface, StateableInterface, \Serializable, StorableInterface
{
    use TransactionModifierStateTrait;
    use TransactionModifierSerializeTrait;
    use ParentReferenceTrait;

    const IDENTIFIER = 'deal_handler';
    const TOTAL_KEY = 'total';
    const CONDITIONS_KEY = 'conditions';
    const RESULT_KEY = 'result';

    protected $data = array();

    protected $stateService;
    protected $eventService;
    protected $dealID;

    public function __construct(State $stateService, EventDispatcherInterface $eventService, $dealID)
    {
        $this->stateService = $stateService;
        $this->eventService = $eventService;
        $this->dealID = $dealID;
    }

    public function setResult($result)
    {
        $this->data[self::RESULT_KEY] = $result;
    }

    public function addCondition($condition)
    {
        $this->data[self::CONDITIONS_KEY][] = $condition;
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

    public function updateTotal()
    {
        $conditionsFailure = false;

        foreach ($this->data[self::CONDITIONS_KEY] as $condition) {
            if (!$condition->met()) {

                $conditionsFailure = true;

                break;
            }
        }

        if ($conditionsFailure) {

            $total = 0;

        } else {

            $total = $this->data[self::RESULT_KEY]->process();
        }

        $this->data[self::TOTAL_KEY] = $total;

        $this->saveState();

        $this->eventService->dispatch(Events::TOTAL_UPDATED);
    }

    /**
     * Indicates the type of amount the modifier will return
     * Must return a constant from TransactionModifierTypes
     */
    public function getType()
    {
        return TransactionModifierTypes::NEUTRAL;
    }

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

    public function getStorableBackendIdentifiers()
    {
        return array(
            Backend::IDENTIFIER
        );
    }
}
