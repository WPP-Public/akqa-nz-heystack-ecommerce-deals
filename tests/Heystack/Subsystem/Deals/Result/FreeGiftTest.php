<?php

namespace Heystack\Deals\Test\Result;

use Heystack\Deals\Result\FreeGift;

class FreeGiftTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var FreeGift
     */
    protected $result;
    protected $adaptableConfigurationStub;
    protected $eventDispatcherStub;
    protected $purchasableHolderStub;
    protected $dataObjectHandlerStub;

    protected function setUp()
    {

        $this->eventDispatcherStub = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->purchasableHolderStub = $this->getMockBuilder('Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectHandlerStub = $this->getMockBuilder('Heystack\Core\DataObjectHandler\DataObjectHandlerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->adaptableConfigurationStub = $this->getMockBuilder('Heystack\Deals\AdaptableConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function configureStub(
        $getConfigMap = [
            [
                FreeGift::PURCHASABLE_CLASS, 'purchasable'
            ],
            [
                FreeGift::PURCHASABLE_ID, 1
            ]
        ],
        $hasConfigMap = [
            [
                FreeGift::PURCHASABLE_CLASS, 'purchasable'
            ],
            [
                FreeGift::PURCHASABLE_ID, 1
            ]
        ])
    {
        $this->adaptableConfigurationStub->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap($getConfigMap)
            );

        $this->adaptableConfigurationStub->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap($hasConfigMap)
            );

        $this->result = new FreeGift(
            $this->eventDispatcherStub,
            $this->purchasableHolderStub,
            $this->dataObjectHandlerStub,
            $this->adaptableConfigurationStub
        );
    }

    public function testGetDescription()
    {
        $this->configureStub();

        $this->assertEquals('Free Purchasable: purchasable1', $this->result->getDescription());

    }

    public function testGetPurchasable()
    {
        $this->configureStub();

        $dealPurchasable = $this->getMockBuilder('Heystack\Deals\Interfaces\DealPurchasableInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dealPurchasableTwo = $this->getMockBuilder('Heystack\Ecommerce\Purchasable\Interfaces\PurchasableInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataObjectHandlerStub->expects($this->any())
            ->method('getDataObjectById')
            ->will(
                $this->returnValue($dealPurchasableTwo)
            );

        $this->purchasableHolderStub->expects($this->any())
            ->method('getPurchasable')
            ->will(
                $this->onConsecutiveCalls($dealPurchasable, $dealPurchasableTwo)
            );

        $this->assertSame($dealPurchasable, $this->result->getPurchasable());
        $this->assertSame($dealPurchasableTwo, $this->result->getPurchasable());

    }

    public function testSetDealHandler()
    {
        $dealHandler = $this->getMockBuilder('Heystack\Deals\DealHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configureStub();
        $this->result->setDealHandler($dealHandler);
        $this->assertAttributeNotEmpty('dealHandler', $this->result);
    }

    /**
     * @depends testSetDealHandler
     */
    public function testGetDealHandler()
    {
        $dealHandler = $this->getMockBuilder('Heystack\Deals\DealHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configureStub();
        $this->result->setDealHandler($dealHandler);
        $this->assertSame($dealHandler, $this->result->getDealHandler());
    }

    /**
     * @depends testGetPurchasable
     * @depends testSetDealHandler
     */
    public function testProcess()
    {
        $this->configureStub();

        $dealPurchasable = $this->getMockBuilder('Heystack\Deals\Interfaces\DealPurchasableInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dealPurchasable->expects($this->any())
            ->method('getUnitPrice')
            ->will(
                $this->returnValue(10)
            );

        $this->purchasableHolderStub->expects($this->any())
            ->method('getPurchasable')
            ->will(
                $this->returnValue($dealPurchasable)
            );

        $dealHandler = $this->getMockBuilder('Heystack\Deals\DealHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $dealHandler->expects($this->any())
            ->method('getConditionsMetCount')
            ->will(
                $this->returnValue(2)
            );

        $this->assertEquals(20, $this->result->process($dealHandler));
    }

    public function testGetSubscribedEvents()
    {
        $this->configureStub();
        $this->assertTrue(is_array($this->result->getSubscribedEvents()));
    }

    /**
     * @depends testSetDealHandler
     */
    public function testOnConditionsNotMet()
    {

        $this->configureStub();

        $dealPurchasable = $this->getMockBuilder('Heystack\Deals\Interfaces\DealPurchasableInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->purchasableHolderStub->expects($this->any())
            ->method('saveState')
            ->will(
                $this->onConsecutiveCalls($dealPurchasable)
            );

        $dealIdentifier = $this->getMockBuilder('Heystack\Core\Identifier\Identifier')
            ->disableOriginalConstructor()
            ->getMock();

        $dealIdentifier->expects($this->any())
            ->method('isMatch')
            ->will(
                $this->returnValue(true)
            );

        $dealHandler = $this->getMockBuilder('Heystack\Deals\DealHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $dealHandler->expects($this->any())
            ->method('getIdentifier')
            ->will(
                $this->returnValue($dealIdentifier)
            );

        $event = $this->getMockBuilder('Heystack\Deals\Events\ConditionEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getDeal')
            ->will(
                $this->returnValue($dealHandler)
            );

        $this->result->setDealHandler($dealHandler);
        $this->result->onConditionsNotMet($event);


    }


}