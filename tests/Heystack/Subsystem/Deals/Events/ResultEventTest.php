<?php

namespace Heystack\Deals\Events;

class ResultEventTest extends \PHPUnit_Framework_TestCase
{

    protected $event;
    protected $result;
    protected $state;
    protected $eventDispatcher;

    protected function setUp()
    {

        $this->eventDispatcher = $this->getMockBuilder('Heystack\Core\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher->expects($this->any())
            ->method('getDispatcher')
            ->will($this->returnSelf());

        $this->result = $this->getMockBuilder('Heystack\Deals\Interfaces\ResultInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = new ResultEvent($this->result);

    }

    public function testIsNotInitiallyEmpty()
    {

        $this->assertAttributeNotEmpty('result', $this->event);

    }

    public function testGetResult()
    {
        $this->assertEquals($this->event->getResult(), $this->result);
    }

    public function testGetDispatcher()
    {
        $this->assertEquals($this->event->getDispatcher(), NULL);
    }

}
