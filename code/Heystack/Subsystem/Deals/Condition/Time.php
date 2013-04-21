<?php

namespace Heystack\Subsystem\Deals\Condition;

use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class Time implements ConditionInterface
{

    public static $time_format = 'd-m-Y H:i:s';
    protected $startTime;
    protected $endTime;
    protected $currentTime;

    public function __construct(AdaptableConfigurationInterface $configuration)
    {
        
        if($configuration->hasConfig('start')){
            
            $this->startTime = strtotime($configuration->getConfig('start'));
            
        }else{
            
            throw new \Exception('Time Condition needs a start time configuration');
            
        }

        if ($configuration->hasConfig('end')) {

            $this->endTime = strtotime($configuration->getConfig('end'));

        }
    }

    public function met(Array $data = null)
    {
        if(!is_null($data) && is_array($data) && isset($data['Time'])){
            $this->currentTime = $data['Time'];
        }else{
            $this->currentTime = time();
        }

        if ($this->startTime && $this->endTime) {
            return ($this->currentTime > $this->startTime) && ($this->currentTime < $this->endTime);
        }

        if ($this->startTime && !$this->endTime) {
            return ($this->currentTime > $this->startTime);
        }

        if ($this->endTime && !$this->endTime) {
            return ($this->currentTime < $this->endTime);
        }

        return false;
    }

    public function description()
    {
        if ($this->startTime && $this->endTime) {
            return 'current time: ' . date(self::$time_format, $this->currentTime) .  ' is between start time: ' . date(self::$time_format,$this->startTime) . ' and end time: ' . date(self::$time_format,$this->endTime);
        }

        if ($this->startTime && !$this->endTime) {
            return 'current time: ' . date(self::$time_format, $this->currentTime) .  ' is after start time: ' . date(self::$time_format,$this->startTime);
        }

        if ($this->endTime && !$this->endTime) {
            return 'current time: ' . date(self::$time_format, $this->currentTime) .  ' is before end time: ' . date(self::$time_format,$this->endTime);
        }

        return 'condition is invalid, please investigate';
    }

}
