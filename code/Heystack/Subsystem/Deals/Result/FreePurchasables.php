<?php

namespace Heystack\Subsystem\Deals\Result;

use Heystack\Subsystem\Core\State\State;
use Heystack\Subsystem\Deals\Interfaces\ResultInterface;
use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Subsystem\Deals\Events;

use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Heystack\Subsystem\Deals\Traits\ResultTrait;

use Heystack\Subsystem\Core\Identifier\Identifier;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class FreePurchasables implements ResultInterface
{
    use ResultTrait;
    
    const IDENTIFIER = 'free_purchasables';
    const PURCHASABLES_KEY = 'purchasables';
    const PROCESSED_KEY = 'processed';
    const TOTAL_KEY = 'total';
    const SELECTED_PURCHASABLE_KEY = 'selected_purchasable';
    
    protected $eventService;
    protected $purchasableHolder;
    protected $stateService;
    protected $configuration;
    
    protected $data = array();

    public function __construct(EventDispatcherInterface $eventService, PurchasableHolderInterface $purchasableHolder, State $stateService, AdaptableConfigurationInterface $configuration)
    {
        $this->eventService = $eventService;
        $this->purchasableHolder = $purchasableHolder;
        $this->stateService = $stateService;
        $this->configuration = $configuration;
        
        if(!$configuration->hasConfig('purchasable_identifiers')){
            
            throw new \Exception('Free Purchasables Result requires a purchasable_identifiers configuration value');
            
        }
        
    }
    
    protected function saveState()
    {
        $stateData = $this->stateService->getByKey(self::IDENTIFIER);
        
        $stateData[$this->dealIdentifier] = $this->data;
        
        $this->stateService->setByKey(self::IDENTIFIER, $stateData);
    }
    
    
    protected function restoreState()
    {
        $stateData = $this->stateService->getByKey(self::IDENTIFIER);
        
        $this->data = isset($stateData[$this->dealIdentifier]) ? $stateData[$this->dealIdentifier] : array();
    }
    
    public function description()
    {
        return 'The product (' . $this->purchasable->getIdentifier()->getFull() . ') is now priced at ' . $this->value;
    }

    public function process()
    {
        $this->restoreState();
        
        if(!isset($this->data[self::PURCHASABLES_KEY]) || !is_array($this->data[self::PURCHASABLES_KEY])){
                
            $this->data[self::PURCHASABLES_KEY] = array();

            foreach($this->configuration->getConfig('purchasable_identifiers') as $identifier){

                //Seaparate the ID from the ClassName in the Identifier
                preg_match('|^([a-z]+)([\d]+)$|i', $identifier, $match);

                $purchasable = \DataObject::get_by_id($match[1], $match[2]);

                if($purchasable instanceof $match[1]){

                    array_push($this->data[self::PURCHASABLES_KEY], $purchasable);

                }

            }

        }
        
        if(!isset($this->data[self::PROCESSED_KEY]) || $this->data[self::PROCESSED_KEY] == false){
        
            //Directly add the purchasable to the purchasableHolder if there is only one as a gift
            if(count($this->data[self::PURCHASABLES_KEY]) == 1 && !isset($this->data[self::SELECTED_PURCHASABLE_KEY])){

                $purchasable = reset($this->data[self::PURCHASABLES_KEY]);

                $this->purchasableHolder->addPurchasable($purchasable, 1);
                
                $this->data[self::SELECTED_PURCHASABLE_KEY] = $purchasable;

                $this->data[self::PROCESSED_KEY] = true;
                
                $this->data[self::TOTAL_KEY] = $purchasable->getPrice();
                
                $this->saveState();

                $this->eventService->dispatch(Events::RESULT_PROCESSED);
                
                return $this->data[self::TOTAL_KEY];
            }
            
            
            //Add the selected purchasable to the purchasableHolder
            if(isset($this->data[self::SELECTED_PURCHASABLE_KEY])){
                
                $purchasable = $this->data[self::SELECTED_PURCHASABLE_KEY];
            
                $this->purchasableHolder->addPurchasable($purchasable, 1);

                $this->data[self::PROCESSED_KEY] = true;

                $this->data[self::TOTAL_KEY] = $purchasable->getPrice();
                
                $this->saveState();
                
                $this->eventService->dispatch(Events::RESULT_PROCESSED);
                
                return $this->data[self::TOTAL_KEY];
                    
            }
            
            $this->data[self::PROCESSED_KEY] = false;
            $this->saveState();
            
        
        }
        
        return 0;
        
    }

}
