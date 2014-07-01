<?php

namespace Heystack\Deals\Condition;

use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Deals\Interfaces\ConditionAlmostMetInterface;
use Heystack\Deals\Interfaces\ConditionInterface;

/**
 * Determine whether the current date is greater than the start date.
 *
 * @copyright  Heyday
 * @author Stevie Mayhew <stevie@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class StartDate implements ConditionInterface, ConditionAlmostMetInterface
{
    const CONDITION_TYPE = 'StartDate';
    const START_KEY = 'start';

    /**
     * @var string
     */
    public static $time_format = 'd-m-Y';
    /**
     * @var int
     */
    protected $startDate;
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

        if ($configuration->hasConfig(self::START_KEY)) {

            $this->startDate = strtotime($configuration->getConfig(self::START_KEY));

        } else {

            throw new \Exception('Start Date Condition requires a start date');

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
        return $this->currentTime > $this->startDate;
    }

    public function almostMet()
    {
        return $this->met();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Valid if current date greater than: ' . date(self::$time_format, $this->startDate);
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

    /**
     * @return int
     */
    public function getPriority()
    {
        return 0;
    }
}
