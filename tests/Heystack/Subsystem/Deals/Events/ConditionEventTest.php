<?php

namespace Heystack\Subsystem\Deals\Events;

class ConditionEventTest extends \PHPUnit_Framework_TestCase
{

    protected $event;
    protected $dealHandler;
    protected $state;
    protected $eventDispatcher;

    protected function setUp()
    {

        $this->state = $this->getMockBuilder('Heystack\Subsystem\Core\State\State')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->getMockBuilder('Heystack\Subsystem\Core\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dealHandler = $this->getMockBuilder('Heystack\Subsystem\Deals\DealHandler')
            ->setConstructorArgs(array($this->state, $this->eventDispatcher, 'testDeal', null))
            ->getMock();

        $this->event = new ConditionEvent($this->dealHandler);

//        $this->event->expects($this->any())
//            ->method('getDeal')
//            ->will($this->returnValue($this->dealHandler));

    }

    public function testGetDeal()
    {
        $this->assertEquals($this->event->getDeal(), $this->dealHandler);
    }

    public function testIsNotInitiallyEmpty()
    {

        $this->assertAttributeNotEmpty('deal', $this->event);

    }



}
