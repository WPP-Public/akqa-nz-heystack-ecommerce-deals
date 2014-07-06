<?php

namespace Heystack\Deals\Condition;

use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Deals\Interfaces\ConditionInterface;
use Heystack\Ecommerce\Locale\Interfaces\LocaleServiceInterface;
use Heystack\Ecommerce\Locale\Traits\HasLocaleServiceTrait;

/**
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 */
class HasZone implements ConditionInterface
{
    use HasLocaleServiceTrait;

    const CONDITION_TYPE = 'ZoneCondition';
    const COUNTRY_CODES = 'country_codes';

    /**
     * @var array
     */
    protected $countryCodes;

    /**
     * @param LocaleServiceInterface $localeService
     * @param AdaptableConfigurationInterface $configuration
     * @throws \Exception when the configuration is not setup properly
     */
    public function __construct(
        LocaleServiceInterface $localeService,
        AdaptableConfigurationInterface $configuration
    ) {
        if ($configuration->hasConfig(self::COUNTRY_CODES)) {

            $this->countryCodes = $configuration->getConfig(self::COUNTRY_CODES);

        } else {

            throw new \Exception('Zone Condition requires an array of allowed country codes to be configured');

        }

        $this->localeService = $localeService;
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
        $activeCountryCode = $this->localeService->getActiveCountry()->getCountryCode();

        if ($activeCountryCode){

            if (in_array($activeCountryCode, $this->countryCodes)){

                return 1;

            }

        }

        return 0;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'This Deal can only be activated from within the ff countries: ' . implode(',',$this->countryCodes);
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 0;
    }
}
