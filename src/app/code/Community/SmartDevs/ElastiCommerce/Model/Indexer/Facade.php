<?php

/**
 * elasticommerce magento indexer facade
 *
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Facade
{

    const TYPE_INDEX_XML_PATH = 'elasticommerce/indexer_types';

    /**
     * flag if current process is a full reindex
     *
     * @var bool
     */
    protected $isFullReindex = false;

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
    protected $mageIndexerTypes = null;

    /**
     * @var SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface[]
     */
    protected $indexerTypeInstances = null;

    /**
     * @var SmartDevs_ElastiCommerce_Indexer[]
     */
    protected $indexerClient = [];

    /**
     * SmartDevs_ElastiCommerce_Model_Indexer_Facade constructor.
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
            //if $classname is a config node force it to be string
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
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface
     */
    protected function createIndexerTypeInstance($type)
    {
        if (false === array_key_exists($type, $this->indexerTypes)) {
            Mage::throwException(sprintf('invalid indexer type "%s"', $type));
        }
        /** @var SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface $indexerTypeInstance */
        $indexerTypeInstance = Mage::getSingleton($this->indexerTypes[$type]);
        if (false === $indexerTypeInstance instanceOf SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface) {
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
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface
     */
    protected function getIndexerTypeInstance($type)
    {
        if (false === isset($this->indexerTypeInstances[$type])) {
            $this->indexerTypeInstances[$type] = $this->createIndexerTypeInstance($type);
        }
        $this->indexerTypeInstances[$type]->setStore($this->getStore());
        if (isset($this->indexerClient[(int)$this->getStoreId()])) {
            $this->indexerTypeInstances[$type]->setIndexerClient(
                $this->indexerClient[(int)$this->getStoreId()]
            );
        }
        return $this->indexerTypeInstances[$type];
    }

    /**
     * @param $storeId
     * @return SmartDevs_ElastiCommerce_Indexer
     */
    protected function getIndexerClient($storeId)
    {
        if (!isset($this->indexerClient[(int)$storeId])) {
            $this->indexerClient[(int)$storeId] = Mage::helper('elasticommerce/factory')->createIndexer((int)$this->getStoreId());
        }
        return $this->indexerClient[(int)$storeId];
    }

    /**
     * set current process is full reindex
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Facade
     */
    protected function setIsFullReindex($value)
    {
        if ($value === false || $value === true) {
            $this->isFullReindex = (bool)$value;
        }
        return $this;
    }

    /**
     * get current process is full reindex
     *
     * @return bool
     */
    protected function isFullReindex()
    {
        return $this->isFullReindex;
    }

    /**
     * set current store scope
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Facade
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

    protected function createMapping()
    {

    }

    /**
     * create new index for storing data
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Facade
     */
    protected function createIndex()
    {
        $this->indexerClient[$this->getStore()->getId()] = Mage::helper('elasticommerce/factory')->createIndexer((int)$this->getStoreId());
        #$config = Mage::helper('elasticommerce/factory')->createConfig((int)$this->getStoreId());
        #$indexer =
        #$settings = $this->createIndexSettings();
        #$mappings = $this->createIndexMappings();
        #$indexer = Mage::helper('elasticommerce/factory')->getIndexer($this->getStoreId());
        //loop over all types to create new mapping
        #$indexer->getMappings()->getMapping();
        return $this;
    }

    /**
     * rotate index to new alias
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Facade
     */
    protected function rotateIndex()
    {
        #$this->getClient()->indexRotate($this->getIndexName(), $this->getIndexAlias());
        return $this;
    }

    /**
     * Rebuild ElastiCommerce Index for all stores
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Facade
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
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Facade
     */
    public function rebuild($store = null)
    {
        //set current store scope
        $this->setStore($store);
        //set flag current process is full reindexing
        $this->setIsFullReindex(true);
        Mage::dispatchEvent('elasticommerce_rebuild_store_before', array('indexer' => $this, 'store' => $this->getStore()));
        //create new index
        $this->createIndex();
        //reindex data for indexer_types
        foreach ($this->getAllIndexerTypes() as $type) {
            $this->getIndexerTypeInstance($type)->reindexStore();
        }
        // refresh index
        //$this->getAdapter()->refreshIndex();
        // rotate index alias
        //$this->rotateIndex();
        Mage::dispatchEvent('elasticommerce_rebuild_store_after', array('indexer' => $this, 'store' => $this->getStore()));
        //reset flag current process is full reindexing
        $this->setIsFullReindex(false);
        return $this;
    }
}