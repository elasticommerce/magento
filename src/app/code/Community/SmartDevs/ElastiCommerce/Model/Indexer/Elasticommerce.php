<?php

/**
 * elasticommerce indexer model
 *
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Elasticommerce
{
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
    protected $_store = null;

    /**
     * current store id
     *
     * @var int
     */
    protected $_storeId = null;

    /**
     * current website related to store
     *
     * @var Mage_Core_Model_Website
     */
    protected $_website = null;

    /**
     * current website id related to store
     *
     * @var int
     */
    protected $_websiteId = null;

    /**
     * current index name
     *
     * @var string
     */
    protected $_indexName = null;

    /**
     * current index alias
     *
     * @var string
     */
    protected $_indexAlias = null;

    /**
     * all indexer type instances
     *
     * @var SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract[]
     */
    private $_indexerTypes = null;

    /**
     * get current process is full reindex
     *
     * @param null|bool
     * @return bool
     */
    public function isFullReindex($value = null)
    {
        if ($value === false || $value === true) {
            $this->_isFullReindex = $value;
            return $this;
        }
        return $this->_isFullReindex;
    }

    /**
     * set current store scope and reset model
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Elasticommerce
     */
    public function setStore($store)
    {
        try {
            $store = Mage::app()->getStore($store);
            if ($store instanceof Mage_Core_Model_Store && $store->getId() > 0) {
                $this->_store = $store;
                $this->_storeId = $store->getId();
                $this->_website = $this->_store->getWebsite();
                $this->_websiteId = $this->_store->getWebsite()->getId();
                $this->_clientInstance = null;
                $this->_indexName = null;
                $this->_indexAlias = null;
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
        return $this->_store;
    }

    /**
     * get current website id
     *
     * @return Mage_Core_Model_Website
     */
    public function getWebsite()
    {
        return $this->_website;
    }

    /**
     * get current store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->_storeId;
    }

    /**
     * get current website id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->_websiteId;
    }


    /**
     * @param $className model name
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    protected function getTypeIndexer($className)
    {
        //if $classname is a confg node force it to be string
        if ($className instanceof Mage_Core_Model_Config_Element) {
            $className = (string)$className;
        }
        //get instance and add it to array cache
        if (false === isset($this->_entityIndexerModel[$className]) || null === $this->_entityIndexerModel[$className]) {
            $this->_entityIndexerModel[$className] = Mage::getModel((string)$className, array('indexer' => $this));
        }
        return $this->_entityIndexerModel[$className];
    }

    /**
     * get all indexer types
     */
    private function getIndexerTypes()
    {
        if (null === $this->_indexerTypes) {
            foreach (Mage::getConfig()->getNode('elasticommerce/indexer_types')->children() as $name => $classAlias) {
                //if $classname is a confg node force it to be string
                if ($classAlias instanceof Mage_Core_Model_Config_Element) {
                    $classAlias = (string)$classAlias;
                }
                $this->_indexerTypes[$name] = Mage::getSingleton($classAlias);
            }
        }
        return array_keys($this->_indexerTypes);
    }

    /**
     * get indexer instance
     *
     * @param $name     name of the indexer
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     * @throws Mage_Core_Exception
     */
    protected function getIndexerTypeInstance($name)
    {
        if (false === in_array($name, $this->getIndexerTypes())) {
            Mage::throwException(sprintf('invalid indexer type "%s"', $name));
        }
        if (false === $this->_indexerTypes[$name] instanceOf SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface) {
            Mage::throwException(sprintf('Indexer "%s" should use interface "%s"', $name, 'SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface'));
        }
        return $this->_indexerTypes[$name];
    }

    /**
     * get current index name
     *
     * @return string
     */
    public function getIndexName()
    {
        if (null === $this->_indexName) {
            $this->_indexName = sprintf('%s_%s',
                $this->getWebsite()->getCode(),
                $this->getStore()->getCode());
        }
        return $this->_indexName;
    }

    /**
     * get current index alias
     *
     * @return string
     */
    public function getIndexAlias()
    {
        if (null === $this->_indexAlias) {
            $this->_indexAlias = sprintf('%s_%s_%u',
                $this->getWebsite()->getCode(),
                $this->getStore()->getCode(),
                time());
        }
        return $this->_indexAlias;
    }

    /**
     * create new index for storing data
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Elasticommerce
     */
    protected function createIndex()
    {
        #if (false === $this->getClient()->indexCreate($this->getIndexAlias())) {
        #    Mage::throwException(sprintf('Unable to create index "%s".', $this->getIndexAlias()));
        #}
        // apply new mapping
        return $this;
    }

    /**
     * refresh index
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Elasticommerce
     */
    protected function refreshIndex()
    {
        #$this->getClient()->indexRefresh($this->getIndexName());
        return $this;
    }

    /**
     * rotate index to new alias
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Elasticommerce
     */
    protected function rotateIndex()
    {
        #$this->getClient()->indexRotate($this->getIndexName(), $this->getIndexAlias());
        return $this;
    }

    /**
     * Rebuild Elasticsearch Catalog Product Data for all stores
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Elasticommerce
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
     * Rebuild Elasticsearch Catalog Product Data for specific store
     *
     * @param int|string|Mage_Core_Model_Store
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Elasticommerce
     */
    public function rebuild($store = null)
    {
        //set current store scope
        $this->setStore($store);
        //set flag current process is full reindexing
        $this->isFullReindex(true);
        Mage::dispatchEvent('elasticommerce_rebuild_store_before', array('indexer' => $this, 'store' => $this->getStore()));
        //create new index
        $this->createIndex();
        //reindex data for indexer_types
        foreach ($this->getIndexerTypes() as $type) {
            $this->getIndexerTypeInstance($type)->reindex($this->getStore());
        }
        // refresh index
        $this->refreshIndex();
        // rotate index alias
        $this->rotateIndex();
        Mage::dispatchEvent('elasticommerce_rebuild_store_after', array('indexer' => $this, 'store' => $this->getStore()));
        //reset flag current process is full reindexing
        $this->isFullReindex(false);
        return $this;
    }
}