<?php

namespace Heystack\Subsystem\Deals\Condition;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\ConditionInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class Purchasable implements ConditionInterface
{

    /**
     * @var \Heystack\Subsystem\Core\Identifier\IdentifierInterface
     */
    protected $purchasableIdentifier;
    protected $purchasableHolder;

    public function __construct(PurchasableHolderInterface $purchasableHolder, AdaptableConfigurationInterface $configuration)
    {
        if ($configuration->hasConfig('purchasable_identifier')) {

            $this->purchasableIdentifier = new Identifier($configuration->getConfig('purchasable_identifier'));

        } else {

            throw new \Exception('Purchasable Condition requires a purchasable_identifier configuration value');

        }

        $this->purchasableHolder = $purchasableHolder;

    }

    public function met(array $data = null)
    {

        if (!is_null($data) && is_array($data) && isset($data['PurchasableIdentifier'])) {
            return $this->purchasableIdentifier->isMatch($data['PurchasableIdentifier']);

        }

        if (is_array($this->purchasableHolder->getPurchasablesByPrimaryIdentifier($this->purchasableIdentifier))) {
            return true;

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
