<?php

/**
 * elasticommerce magento indexer facade
 *
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Magento_Facade
{

    const TYPE_INDEX_XML_PATH = 'elasticommerce/indexer_types';

    /**
     * flag if current process is a full reindex
     *
     * @var bool
     */
    protected $_isFullReindex = false;

    /**
     * current store object
     *
     * @var Mage_Core_Model_Store
     */
    protected $store = null;

    /**
     * all indexer types
     *
     * @var string[]
     */
    protected $indexerTypes = null;

    /**
     * @var SmartDevs_ElastiCommerce_Model_Indexer_Magento_Interface[]
     */
    protected $indexerTypeInstances = null;

    /**
     * SmartDevs_ElastiCommerce_Model_Indexer_Magento_Facade constructor.
     */
    public function __construct()
    {
        $this->initIndexerTypes();
    }

    /**
     * init all indexer types and class aliases
     */
    protected function initIndexerTypes()
    {
        foreach (Mage::getConfig()->getNode(self::TYPE_INDEX_XML_PATH)->children() as $name => $classAlias) {
            //if $classname is a confg node force it to be string
            if ($classAlias instanceof Mage_Core_Model_Config_Element) {
                $classAlias = (string)$classAlias;
            }
            $this->indexerTypes[$name] = $classAlias;
        }
    }

    /**
     * get array of indexer type codes
     *
     * @return string[]
     */
    protected function getAllIndexerTypes()
    {
        return array_keys($this->indexerTypes);
    }

    /**
     * create an new indexer type instance by given code
     *
     * @param $type
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Magento_Interface
     */
    protected function createIndexerTypeInstance($type)
    {
        if (false === array_key_exists($type, $this->indexerTypes)) {
            Mage::throwException(sprintf('invalid indexer type "%s"', $type));
        }
        /** @var SmartDevs_ElastiCommerce_Model_Indexer_Magento_Interface $indexerTypeInstance */
        $indexerTypeInstance = Mage::getSingleton($this->indexerTypes[$type]);
        if (false === $indexerTypeInstance instanceOf SmartDevs_ElastiCommerce_Model_Indexer_Magento_Interface) {
            Mage::throwException(sprintf('Indexer "%s" should use interface "%s"',
                $type,
                'SmartDevs_ElastiCommerce_Model_Indexer_Magento_Interface'));
        }
        return $indexerTypeInstance;
    }

    /**
     * get indexer type instance by code
     *
     * @param string $type
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Magento_Interface
     */
    protected function getIndexerTypeInstance($type)
    {
        if (false === isset($this->indexerTypeInstances[$type])) {
            $this->indexerTypeInstances[$type] = $this->createIndexerTypeInstance($type);
        }
        return $this->indexerTypeInstances[$type];
    }

    /**
     * get current process is full reindex
     *
     * @param null|bool
     * @return bool
     */
    protected function isFullReindex($value = null)
    {
        if ($value === false || $value === true) {
            $this->_isFullReindex = $value;
            return $this;
        }
        return $this->_isFullReindex;
    }

    /**
     * set current store scope
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Magento_Facade
     */
    public function setStore($store)
    {
        try {
            $store = Mage::app()->getStore($store);
            if ($store instanceof Mage_Core_Model_Store && $store->getId() > 0) {
                $this->store = $store;
                return $this;
            }
        } catch (Mage_Core_Model_Store_Exception $e) {
            Mage::throwException('unknown store given.');
        }
        return $this;
    }

    /**
     * get current store scope
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * get current store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->getStore()->getId();
    }

    /**
     * get current website id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->getStore()->getWebsiteId();
    }

    /**
     * create new index for storing data
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Magento_Facade
     */
    protected function createIndex()
    {
        //loop over all types to create new mapping
        return $this;
    }

    /**
     * refresh index
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Magento_Facade
     */
    protected function refreshIndex()
    {
        #$this->getClient()->indexRefresh($this->getIndexName());
        return $this;
    }

    /**
     * rotate index to new alias
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Magento_Facade
     */
    protected function rotateIndex()
    {
        #$this->getClient()->indexRotate($this->getIndexName(), $this->getIndexAlias());
        return $this;
    }

    /**
     * Rebuild ElastiCommerce Index for all stores
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Magento_Facade
     */
    public function reindexAll()
    {
        Mage::dispatchEvent('elasticommerce_rebuild_all_before', array('indexer' => $this));
        foreach (Mage::app()->getStores() as $store) {
            $this->rebuild($store);
        }
        Mage::dispatchEvent('elasticommerce_rebuild_all_after', array('indexer' => $this));
        return $this;
    }

    /**
     * Rebuild ElastiCommerce Index for specific store
     *
     * @param int|Mage_Core_Model_Store
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Magento_Facade
     */
    public function rebuild($store = null)
    {
        //set current store scope
        $this->setStore($store);
        //set flag current process is full reindexing
        $this->isFullReindex(true);
        Mage::dispatchEvent('elasticommerce_rebuild_store_before', array('indexer' => $this, 'store' => $this->getStore()));
        //create new index
        //$this->createIndex();
        //reindex data for indexer_types
        foreach ($this->getAllIndexerTypes() as $type) {
            $this->getIndexerTypeInstance($type)->reindexStore($this->getStore());
        }
        // refresh index
        //$this->getAdapter()->refreshIndex();
        // rotate index alias
        //$this->rotateIndex();
        Mage::dispatchEvent('elasticommerce_rebuild_store_after', array('indexer' => $this, 'store' => $this->getStore()));
        //reset flag current process is full reindexing
        $this->isFullReindex(false);
        return $this;
    }
}