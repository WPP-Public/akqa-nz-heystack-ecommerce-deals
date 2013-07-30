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

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 *
 * @author  Stevie Mayhew <stevie@heyday.co.nz>
 * @package Heystack
 */
class Event extends SymfonyEvent
{
    /**
     * @var null
     */
    private $identifier = null;

    /**
     * @param $reference
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return null
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
