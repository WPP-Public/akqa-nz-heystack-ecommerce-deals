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

    }
    /**
     * @param array $data
     * @return bool
     */
    public function met(array $data = null)
    {
        if (is_array($data) && isset($data[self::CONDITION_TYPE])) {
            $this->currentTime = $data[self::CONDITION_TYPE];
        } else {
            $this->currentTime = time();
        }

        if ($this->startTime && $this->endTime) {
            return ($this->currentTime > $this->startTime) && ($this->currentTime < $this->endTime);
        }

        if ($this->startTime && !$this->endTime) {
            return ($this->currentTime > $this->startTime);
        }

        if ($this->endTime && !$this->startTime) {
            return ($this->currentTime < $this->endTime);
        }

        return false;
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
}
