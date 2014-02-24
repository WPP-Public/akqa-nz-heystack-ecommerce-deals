<?php
namespace Heystack\Deals\Test;

class HasDealHandlerTest extends \PHPUnit_Framework_TestCase
{

    protected $trait;
    protected $dealHandler;

    protected function setUp()
    {
        $this->trait = $this->getObjectForTrait('Heystack\Deals\Traits\HasDealHandler');
        $this->dealHandler = $this->getMockBuilder('Heystack\Deals\DealHandler')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testIsInitiallyEmpty()
    {

        $this->assertAttributeEmpty('dealHandler', $this->trait);

    }

    public function testSetDealHandler()
    {

        $this->trait->setDealHandler($this->dealHandler);

        $this->assertAttributeNotEmpty('dealHandler', $this->trait);

    }

    public function testGetDealHandler()
    {

        $this->trait->setDealHandler($this->dealHandler);

        $handler = $this->trait->getDealHandler();

        $this->assertEquals($handler, $this->dealHandler);

    }
}
