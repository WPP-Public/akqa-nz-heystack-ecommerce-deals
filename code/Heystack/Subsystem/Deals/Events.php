<?php
/**
 * This file is part of the Ecommerce-Shipping package
 *
 * @package Ecommerce-Shipping
 */

/**
 * Shipping namespace
 */
namespace Heystack\Subsystem\Deals;

/**
 * Events holds constant references to triggerable dispatch events.
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Shipping
 * @see Symfony\Component\EventDispatcher
 *
 */
final class Events
{
    /**
     * Indicates that the DealHandler's total has been updated
     */
    const TOTAL_UPDATED       = 'deals.totalupdated';

    /**
     * Indicates that the DealHandler's information has been stored
     */
    const STORED              = 'deals.stored';

    /**
     * Indicates that a result has been processed
     */
    const RESULT_PROCESSED    = 'deals.resultprocessed';

    /**
     * Indicates that a condition was not met. (not relying on the data array passed into the met function)
     */
    const CONDITIONS_NOT_MET  = 'deals.conditionsnotmet';
}
