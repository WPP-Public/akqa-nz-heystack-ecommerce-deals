<?php
/**
 * Created by JetBrains PhpStorm.
 * User: glenn
 * Date: 21/05/13
 * Time: 5:13 PM
 * To change this template use File | Settings | File Templates.
 */

namespace Heystack\Subsystem\Deals\Test;


use Heystack\Subsystem\Deals\Result\FreePurchasable;

class FreePurchasableTest extends \PHPUnit_Framework_TestCase
{
    const PRIMARY_IDENTIFIER = 'Identifier123';
    const PURCHASABLE_PRICE = 215;
    const PURCHASABLE_QUANTITY = 1;

    /**
     * @var FreePurchasable
     */
    protected $freePurchasableResult;

    protected function setUp()
    {
        $adaptableConfigurationStub = $this->getMockBuilder('Heystack\Subsystem\Deals\AdaptableConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $adaptableConfigurationStub->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['purchasable_identifier', self::PRIMARY_IDENTIFIER]
                    ]
                )
            );

        $adaptableConfigurationStub->expects($this->any())
            ->method('hasConfig')
            ->will(
                $this->returnValueMap(
                    [
                        ['purchasable_identifier', true]
                    ]
                )
            );

        $identifierStub = $this->getMockBuilder('Heystack\Subsystem\Core\Identifier\Identifier')
            ->disableOriginalConstructor()
            ->getMock();


        $purchasableStub = $this->getMockBuilder('Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $purchasableStub->expects($this->any())
            ->method('getTotal')
            ->will(
                $this->returnValue(self::PURCHASABLE_PRICE)
            );

        $purchasableStub->expects($this->any())
            ->method('getQuantity')
            ->will(
                $this->returnValue(self::PURCHASABLE_QUANTITY)
            );

        $purchasableStub->expects($this->any())
            ->method('getUnitPrice')
            ->will(
                $this->returnValue(self::PURCHASABLE_PRICE)
            );

        $purchasableStub->expects($this->any())
            ->method('getIdentifier')
            ->will(
                $this->returnValue($identifierStub)
            );

        $purchasableHolderStub = $this->getMockBuilder('Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $purchasableHolderStub->expects($this->any())
            ->method('getPurchasable')
            ->will(
                $this->onConsecutiveCalls($purchasableStub, null)
            );

        $stateStub = $this->getMockBuilder('Heystack\Subsystem\Core\State\State')
            ->disableOriginalConstructor()
            ->getMock();

        $dataobjectHandlerStub = $this->getMockBuilder('Heystack\Subsystem\Core\DataObjectHandler\DataObjectHandlerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dataobjectHandlerStub->expects($this->any())
            ->method('getDataObjectById')
            ->will(
                $this->returnValue($purchasableStub)
            );

        $eventDispatcherStub = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->freePurchasableResult = new FreePurchasable($eventDispatcherStub, $purchasableHolderStub, $stateStub, $dataobjectHandlerStub, $adaptableConfigurationStub);
    }

    public function testGetDescription()
    {
        $this->assertEquals('Free Purchasable:' . self::PRIMARY_IDENTIFIER, $this->freePurchasableResult->getDescription());
    }

    public function testProcess()
    {
        $this->markTestIncomplete();
        return;
        $identifierStub = $this->getMockBuilder('Heystack\Subsystem\Core\Identifier\Identifier')
            ->disableOriginalConstructor()
            ->getMock();

        $identifierStub->expects($this->any())
            ->method('getFull')
            ->will(
                $this->returnValue('dealIdentifier')
            );

        $dealHandlerStub = $this->getMockBuilder('Heystack\Subsystem\Deals\DealHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $dealHandlerStub->expects($this->any())
            ->method('getIdentifier')
            ->will(
                $this->returnValue($identifierStub)
            );

        $dealHandlerStub->expects($this->any())
            ->method('getConditions')
            ->will(
                $this->returnValue(array())
            );

        $this->assertEquals(self::PURCHASABLE_PRICE, $this->freePurchasableResult->process($dealHandlerStub));
    }

}