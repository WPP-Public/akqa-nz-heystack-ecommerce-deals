<?php
namespace Heystack\Subsystem\Deals\Test;

class ConditionEventTest extends \PHPUnit_Framework_TestCase
{

    protected $event;
    protected $dealHandler;

    protected function setUp()
    {
        $this->deal = $this->getMockBuilder('Heystack\Subsystem\Deals\DealHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder('Heystack\Subsystem\Deals\Events\ConditionEvent')
            ->setConstructorArgs(array($this->dealHandler))
            ->getMock();


    }

    public function testIsNotInitiallyEmpty()
    {

        $this->assertNotAttributeEmpty('deal', $this->event);

    }

    public function testGetDeal()
    {

        $this->assertEquals($this->event->getDeal(), $this->dealHandler);

    }

}
