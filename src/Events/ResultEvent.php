<?php

namespace Heystack\Deals\Events;

use Heystack\Deals\Interfaces\ResultInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ResultEvent
 * @package Heystack\Deals
 */
class ResultEvent extends Event
{
    /**
     * @var \Heystack\Deals\Interfaces\ResultInterface
     */
    protected $result;

    /**
     * @param \Heystack\Deals\Interfaces\ResultInterface $result
     */
    public function __construct(ResultInterface $result)
    {
        $this->result = $result;
    }

    /**
     * @return \Heystack\Deals\Interfaces\ResultInterface
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return \Heystack\Core\EventDispatcher
     */
    public function getDispatcher()
    {
        return parent::getDispatcher();
    }
}