<?php
/**
 * This file is part of the Heystack package
 *
 * @package Heystack
 */

/**
 * Storage namespace
 */
namespace Heystack\Deals\Events;

use Heystack\Deals\Interfaces\DealHandlerInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 *
 * @author  Stevie Mayhew <stevie@heyday.co.nz>
 * @package Heystack
 */
class ConditionEvent extends Event
{
    /**
     * @var DealHandlerInterface
     */
    private $deal;

    /**
     * @param DealHandlerInterface $deal
     */
    public function __construct(DealHandlerInterface $deal)
    {
        $this->deal = $deal;
    }

    /**
     * @return DealHandlerInterface
     */
    public function getDeal()
    {
        return $this->deal;
    }

    /**
     * @return \Heystack\Core\EventDispatcher
     */
    public function getDispatcher()
    {
        return parent::getDispatcher();
    }
}
