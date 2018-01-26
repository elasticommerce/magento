<?php
use SmartDevs\ElastiCommerce\Index\Mappings as AbstractMappings;

class SmartDevs_ElastiCommerce_Model_Index_Mappings extends AbstractMappings
{
    /**
     * cache key for storage
     */
    const CACHE_KEY = 'elasticommerce_config_mappings_%s';

    /**
     * cache tag for storage
     */
    const CACHE_TAG = 'ELASTICOMMERCE';

    /**
     * @var Mage_Core_Model_Store
     */
    protected $store = null;

    /**
     * SmartDevs_ElastiCommerce_Model_ElastiCommerce_Index_Mappings constructor.
     *
     * @param array $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        if (false === isset($params['store'])) {
            throw new Exception('missing required parameter "store".');
        }
        $this->setStore($params['store']);
        $this->init();
    }

    /**
     * init required parameters
     * @todo init from cache
     */
    protected function init()
    {
        $this->initMappings();
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return $this
     */
    protected function setStore(Mage_Core_Model_Store $store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * @return Mage_Core_Model_Store
     */
    protected function getStore()
    {
        return $this->store;
    }

    /**
     * name of the config file for the schema
     *
     * @return string
     */
    protected function getSchemaConfigFile()
    {
        return 'schema.xml';
    }

    /**
     * init analyzer from config file
     *
     * @return $this
     * @throws \Exception
     */
    protected function initMappings()
    {
        // try to find valid analyzer config file
        $configFile = sprintf('%s/%s', Mage::getModuleDir('etc', 'SmartDevs_ElastiCommerce'), strtolower($this->getSchemaConfigFile()));
        if (true === $this->isConfigFileReadable($configFile)) {
            $xml = $this->readXmlConfig($configFile);
        }
        if (false === isset($xml) || false === $xml instanceof \SimpleXMLElement) {
            throw new Exception('missing valid schema config file');
        }
        $this->initMappingsFromXml($xml);
        return $this;
    }
}