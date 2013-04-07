<?php
/**
 * This file is part of the Ecommerce-Deals package
 *
 * @package Ecommerce-Deals
 */

/**
 * Dependency Injection namespace
 */
namespace Heystack\Subsystem\Deals\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use Symfony\Component\Config\Definition\Processor;

use Heystack\Subsystem\Core\DependencyInjection\ContainerExtensionConfigProcessor;

/**
 * Container extension for Heystack.
 *
 * If Heystacks services are loaded as an extension (this happens when there is
 * a primary services.yml file in mysite/config) then this is the container
 * extension that loads heystacks services.yml
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class ContainerExtension  extends ContainerExtensionConfigProcessor implements ExtensionInterface
{
    protected $config;

    /**
     * Loads a services.yml file into a fresh container, ready to me merged
     * back into the main container
     *
     * @param  array            $config
     * @param  ContainerBuilder $container
     * @return null
     */
    public function load(array $config, ContainerBuilder $container)
    {
		
		//YamlFileLoader
		$loader = new YamlFileLoader(
            $container,
            new FileLocator(ECOMMERCE_DEALS_BASE_PATH . '/config')
        );

        $loader->load('services.yml');
        
        $this->processConfig($config, $container);
        
        $dealClass = $container->getParameter('deal.data.class');
        
        $deals = \DataObject::get($dealClass);
        
        $dealconfig = array();
        
        if($deals instanceof \DataObjectSet ) foreach($deals->toArray() as $deal){
            
            $dealconfig[$deal->getLabel()] = $deal->getConfigArray();
            
        }
        
        $processor = new Processor();
		$configuration = new DealsConfiguration();
		$this->config = $processor->processConfiguration(
			$configuration,
			array($dealconfig)
		);
    }

    /**
     * Returns the namespace of the container extension
     * @return type
     */
    public function getNamespace()
    {
        return 'deals';
    }

    /**
     * Returns Xsd Validation Base Path, which is not used, so false
     * @return boolean
     */
    public function getXsdValidationBasePath()
    {
        return false;
    }

    /**
     * Returns the container extensions alias
     * @return type
     */
    public function getAlias()
    {
        return 'deals';
    }
    
    public function getConfig()
    {
        return $this->config;
    }

}
