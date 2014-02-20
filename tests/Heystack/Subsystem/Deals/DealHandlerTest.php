<?php
namespace Heystack\Subsystem\Deals\Test;

use Heystack\Subsystem\Core\Storage\Backends\SilverStripeOrm\Backend;
use Heystack\Subsystem\Deals\DealHandler;
use Heystack\Subsystem\Ecommerce\Transaction\TransactionModifierTypes;

class DealHandlerTest extends \PHPUnit_Framework_TestCase
{
    const DEAL_ID = 'deal123';
    const INITIAL_TOTAL = 500;
    const FINAL_TOTAL = 200;
    const CONDITION_DESCRIPTION = 'This is a test condition description';
    const RESULT_DESCRIPTION = 'This is a test result description';
    const PARENT_REFERENCE = 'Test parent reference';

    /**
     * @var DealHandler
     */
    protected $dealHandler;
    
    protected function setUp()
    {
        $stateServiceStub = $this->getStateStub();

        $eventDispatcherStub = $this->getEventStub();

        $this->dealHandler = new DealHandler(
            $stateServiceStub,
            $eventDispatcherStub,
            self::DEAL_ID,
            serialize(
                [
                    'test' => 'message'
                ]
            )
        );
    }
    /**
     * @return mixed
     */
    protected function getStateStub()
    {
        $stateServiceStub = $this->getMockBuilder('Heystack\Subsystem\Core\State\State')
            ->disableOriginalConstructor()
            ->getMock();

        $stateServiceStub->expects($this->any())
            ->method('getByKey')
            ->will(
                $this->returnValue(
                    [
                        DealHandler::TOTAL_KEY => self::INITIAL_TOTAL
                    ]
                )
            );

        return $stateServiceStub;
    }
    
    /**
     * @return mixed
     */
    protected function getEventStub()
    {
        $eventDispatcherStub = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        return $eventDispatcherStub;
    }
    
    protected function getConditionStub()
    {
        $condition = $this->getMockBuilder('Heystack\Subsystem\Deals\Interfaces\ConditionInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $condition->expects($this->any())
            ->method('met')
            ->will(
                $this->returnValue(true)
            );

        $condition->expects($this->any())
            ->method('getDescription')
            ->will(
                $this->returnValue(self::CONDITION_DESCRIPTION)
            );

        return $condition;
    }
    
    protected function getResultStub()
    {
        $result = $this->getMockBuilder('Heystack\Subsystem\Deals\Interfaces\ResultInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $result->expects($this->any())
            ->method('process')
            ->will(
                $this->returnValue(self::FINAL_TOTAL)
            );

        $result->expects($this->any())
            ->method('getDescription')
            ->will(
                $this->returnValue(self::RESULT_DESCRIPTION)
            );

        return $result;
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getPromotionalMessage
     */
    public function testGetPromotionalMessage()
    {
        $this->assertEquals('message', $this->dealHandler->getPromotionalMessage('test'));
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::addCondition
     */
    public function testAddCondition()
    {
        $this->dealHandler->addCondition($condition = $this->getConditionStub());
        $conditions = $this->dealHandler->getConditions();
        $this->assertSame($condition, reset($conditions));

        // test conditions which have a dealhandler attached
        $condition = $this->getMockBuilder('Heystack\Subsystem\Deals\Condition\QuantityOfPurchasablesInCart')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dealHandler->addCondition($condition);
        $conditions = $this->dealHandler->getConditions();

        $this->assertSame($condition, reset($conditions));

    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getIdentifier
     */
    public function testGetIdentifier()
    {
        $this->assertInstanceOf('Heystack\Subsystem\Core\Identifier\Identifier', $this->dealHandler->getIdentifier());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getTotal
     */
    public function testGetTotal()
    {
        $this->dealHandler->setResult($this->getResultStub());

        $this->assertEquals(self::INITIAL_TOTAL, $this->dealHandler->getTotal());

    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::conditionsMet
     */
    public function testConditionsMet()
    {
        $this->dealHandler->addCondition($this->getConditionStub());

        $this->assertTrue($this->dealHandler->conditionsMet());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getType
     */
    public function testGetType()
    {
        $this->assertEquals(TransactionModifierTypes::DEDUCTIBLE, $this->dealHandler->getType());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getStorableData
     */
    public function testGetStorableData()
    {
        $conditionDescription = self::CONDITION_DESCRIPTION;
        $resultDescription = self::RESULT_DESCRIPTION;
        $description = <<<DESCRIPTION
Conditions:
$conditionDescription
Result:
$resultDescription
DESCRIPTION;

        $expectedInitialResult = [
            'id' => 'Deal',
            'parent' => true,
            'flat' => [
                'Total' => self::INITIAL_TOTAL,
                'Description' => $description,
                'ParentID' => self::PARENT_REFERENCE
            ]
        ];

        $expectedFinalResult = [
            'id' => 'Deal',
            'parent' => true,
            'flat' => [
                'Total' => self::FINAL_TOTAL,
                'Description' => $description,
                'ParentID' => self::PARENT_REFERENCE
            ]
        ];

        $this->dealHandler->addCondition($this->getConditionStub());
        $this->dealHandler->setResult($this->getResultStub());
        $this->dealHandler->setParentReference(self::PARENT_REFERENCE);

        $this->assertEquals($expectedInitialResult, $this->dealHandler->getStorableData());

        $this->dealHandler->updateTotal();

        $this->assertEquals($expectedFinalResult, $this->dealHandler->getStorableData());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getStorableIdentifier
     */
    public function testGetStorableIdentifier()
    {
        $this->assertEquals(DealHandler::IDENTIFIER . self::DEAL_ID, $this->dealHandler->getStorableIdentifier());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getSchemaName
     */
    public function testGetSchemaName()
    {
        $this->assertEquals('Deal', $this->dealHandler->getSchemaName());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getStorableBackendIdentifiers
     */
    public function testGetStorableBackendIdentifiers()
    {
        $this->assertEquals([Backend::IDENTIFIER], $this->dealHandler->getStorableBackendIdentifiers());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getParentReference
     */
    public function testGetParentReference()
    {
        $this->dealHandler->setParentReference(self::PARENT_REFERENCE);

        $this->assertEquals(self::PARENT_REFERENCE, $this->dealHandler->getParentReference());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::updateTotal
     */
    public function testUpdateTotal()
    {
        $this->dealHandler->setResult($this->getResultStub());

        $this->dealHandler->updateTotal();

        $this->assertEquals(self::FINAL_TOTAL, $this->dealHandler->getTotal());

        $condition = $this->getMockBuilder('Heystack\Subsystem\Deals\Interfaces\ConditionInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $condition->expects($this->any())
            ->method('met')
            ->will(
                $this->returnValue(false)
            );

        $condition->expects($this->any())
            ->method('getDescription')
            ->will(
                $this->returnValue(self::CONDITION_DESCRIPTION)
            );

        $this->dealHandler->addCondition($condition);
        $this->dealHandler->updateTotal();

        $this->assertEquals(0, $this->dealHandler->getTotal());

    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getConditions
     */
    public function testGetConditions()
    {
        $this->dealHandler->addCondition($condition = $this->getConditionStub());
        $gotConditions = $this->dealHandler->getConditions();
        $this->assertTrue(is_array($gotConditions));
        $this->assertSame(array_pop($gotConditions), $condition);
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getConditionsMetCount
     */
    public function testGetConditionsMetCount()
    {
        $this->dealHandler->addCondition($this->getConditionStub());
        $this->assertEquals(0, $this->dealHandler->getConditionsMetCount());
        $this->dealHandler->conditionsMet();
        $this->assertEquals(1, $this->dealHandler->getConditionsMetCount());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getResult
     */
    public function testGetResult()
    {
        $this->dealHandler->setResult($result = $this->getResultStub());
        $this->assertSame($this->dealHandler->getResult(), $result);
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::almostMet
     * @depends testAddCondition
     */
    public function testAlmostMet()
    {
        $condition = $this->getMockBuilder('Heystack\Subsystem\Deals\Condition\QuantityOfPurchasablesInCart')
            ->disableOriginalConstructor()
            ->getMock();

        $condition->expects($this->any())
            ->method('almostMet')
            ->will(
                $this->returnValue(false)
            );

        $condition->expects($this->any())
            ->method('getType')
            ->will(
                $this->returnValue('test')
            );

        $this->dealHandler->addCondition($this->getConditionStub());
        $this->dealHandler->addCondition($condition);
        $this->assertFalse($this->dealHandler->almostMet());
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::setStateService
     */
    public function testSetStateService()
    {
        $this->dealHandler->setStateService($this->getStateStub());
        $this->assertAttributeNotEmpty('stateService', $this->dealHandler);
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getStateService
     * @depends testSetStateService
     */
    public function testGetStateService()
    {
        $this->dealHandler->setStateService($state = $this->getStateStub());
        $this->assertSame($this->dealHandler->getStateService(), $state);
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::setData
     */
    public function testSetData()
    {
        $this->dealHandler->setData('data');
        $this->assertAttributeNotEmpty('data', $this->dealHandler);
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::getData
     * @depends testSetData
     */
    public function testGetData()
    {
        $this->dealHandler->setData('data');
        $this->assertSame($this->dealHandler->getData(), 'data');
    }

    /**
     * @covers Heystack\Subsystem\Deals\DealHandler::setResult
     * @depends testGetConditions
     */
    public function testSetResult()
    {

        $condition = $this->getConditionStub();

        $result = $this->getMockBuilder('Heystack\Subsystem\Deals\Result\CheapestPurchasableDiscount')
            ->disableOriginalConstructor()
            ->getMock();

        $result->expects($this->any())
            ->method('process')
            ->will(
                $this->returnValue(self::FINAL_TOTAL)
            );

        $result->expects($this->any())
            ->method('getDescription')
            ->will(
                $this->returnValue(self::RESULT_DESCRIPTION)
            );

        $result->expects($this->any())
            ->method('getConditions')
            ->will(
                $this->returnValue(
                    [
                        $condition
                    ]
                )
            );

        $this->dealHandler->setResult($result);

        $this->assertSame($this->dealHandler->getResult(), $result);

    }


}
