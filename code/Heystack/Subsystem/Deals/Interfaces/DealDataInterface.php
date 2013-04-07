<?php

namespace Heystack\Subsystem\Deals\Interfaces;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface DealDataInterface
{
    /**
     * Retrieve the config array for the deal
     * @return Array 
     */
    public function getConfigArray();
    
    /**
     * Retrieve the label of the deal
     * @return String 
     */
    public function getLabel();

}
