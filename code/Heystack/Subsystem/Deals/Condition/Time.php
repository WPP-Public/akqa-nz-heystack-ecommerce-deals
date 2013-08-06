<?php

namespace Heystack\Subsystem\Deals\Condition;

use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class Time implements ConditionInterface
{
    const CONDITION_TYPE = 'Time';
    const START_KEY = 'start';
    const END_KEY = 'end';

    /**
     * @var string
     */
    public static $time_format = 'd-m-Y';
    /**
     * @var int
     */
    protected $startTime;
    /**
     * @var int
     */
    protected $endTime;
    /**
     * @var
     */
    protected $currentTime;
    /**
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception if the configuration does not have a 'start' value
     */
    public function __construct(AdaptableConfigurationInterface $configuration)
    {
        $startAndEndTimesAbsent = true;

        if ($configuration->hasConfig(self::START_KEY)) {

            $this->startTime = strtotime($configuration->getConfig(self::START_KEY));

            $startAndEndTimesAbsent = false;

        }

        if ($configuration->hasConfig(self::END_KEY)) {

            $this->endTime = strtotime($configuration->getConfig(self::END_KEY));

            $startAndEndTimesAbsent = false;

        }

        if ($startAndEndTimesAbsent) {

            throw new \Exception('Time Condition requires either a Start time or an End time');

        }

        // Set up a default currentTime, but allow the value to be overridden through a setter.
        $this->currentTime = time();

    }
    /**
     * @return string that indicates the type of condition this class is implementing
     */
    public function getType()
    {
        return self::CONDITION_TYPE;
    }
    /**
     * @return int
     */
    public function met()
    {
        $met = false;

        if ($this->startTime && $this->endTime) {
            $met = ($this->currentTime > $this->startTime) && ($this->currentTime < $this->endTime);
        }

        if ($this->startTime && !$this->endTime) {
            $met = $this->currentTime > $this->startTime;
        }

        if ($this->endTime && !$this->startTime) {
            $met = $this->currentTime < $this->endTime;
        }

        return $met;
    }
    /**
     * @return string
     */
    public function getDescription()
    {
        $description = array();

        if ($this->startTime) {
            $description[] = 'From: ' . date(self::$time_format, $this->startTime);
        }
        if ($this->endTime) {
            $description[] = 'To: ' . date(self::$time_format, $this->endTime);
        }

        return implode('; ', $description);
    }

    /**
     * @param mixed $currentTime
     */
    public function setCurrentTime($currentTime)
    {
        $this->currentTime = $currentTime;
    }

    /**
     * @return mixed
     */
    public function getCurrentTime()
    {
        return $this->currentTime;
    }
}
