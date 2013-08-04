<?php
/**
 * This file is part of the Heystack package
 *
 * @package Heystack
 */

/**
 * Storage namespace
 */
namespace Heystack\Subsystem\Deals;

use Heystack\Subsystem\Deals\Interfaces\DealHandlerInterface;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 *
 * @author  Stevie Mayhew <stevie@heyday.co.nz>
 * @package Heystack
 */
class ConditionEvent extends SymfonyEvent
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
}
