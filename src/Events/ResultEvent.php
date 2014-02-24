<?php

namespace Heystack\Subsystem\Deals\Events;

use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ResultEvent
 * @package Heystack\Subsystem\Deals
 */
class ResultEvent extends Event
{
    /**
     * @var \Heystack\Subsystem\Deals\Interfaces\ResultInterface
     */
    protected $result;

    /**
     * @param \Heystack\Subsystem\Deals\Interfaces\ResultInterface $result
     */
    public function __construct(ResultInterface $result)
    {
        $this->result = $result;
    }

    /**
     * @return \Heystack\Subsystem\Deals\Interfaces\ResultInterface
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return \Heystack\Subsystem\Core\EventDispatcher
     */
    public function getDispatcher()
    {
        return parent::getDispatcher();
    }
}