<?php

namespace Heystack\Subsystem\Deals\Condition;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Interfaces\PurchasableConditionInterface;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class Purchasable implements PurchasableConditionInterface
{
    /**
     * @var \Heystack\Subsystem\Core\Identifier\IdentifierInterface
     */
    protected $purchasableIdentifier;
    /**
     * @var \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    protected $purchasableHolder;
    /**
     * @param PurchasableHolderInterface      $purchasableHolder
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception if the configuration does not have a purchasable identifier
     */
    public function __construct(PurchasableHolderInterface $purchasableHolder, AdaptableConfigurationInterface $configuration)
    {
        if ($configuration->hasConfig('purchasable_identifier')) {

            $this->purchasableIdentifier = new Identifier($configuration->getConfig('purchasable_identifier'));

        } else {

            throw new \Exception('Purchasable Condition requires a purchasable_identifier configuration value');

        }

        $this->purchasableHolder = $purchasableHolder;

    }
    /**
     * @param array $data
     * @return bool
     */
    public function met(array $data = null)
    {

        if (is_array($data) && isset($data['PurchasableIdentifier'])) {
            return $this->purchasableIdentifier->isMatch(new Identifier($data['PurchasableIdentifier']));

        }

        $purchasables = $this->purchasableHolder->getPurchasablesByPrimaryIdentifier($this->purchasableIdentifier);

        if (is_array($purchasables) && count($purchasables)) {
            return true;

        }

        return false;
    }
    /**
     * @return string
     */
    public function getDescription()
    {

        return 'Must have Purchasable: ' . $this->purchasableIdentifier->getPrimary();

    }
    /**
     * @return Identifier|\Heystack\Subsystem\Core\Identifier\IdentifierInterface
     */
    public function getPurchasableIdentifier()
    {
        return $this->purchasableIdentifier;
    }
}
