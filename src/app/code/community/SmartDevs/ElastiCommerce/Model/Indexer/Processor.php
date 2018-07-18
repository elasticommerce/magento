<?php

/**
 * elasticommerce magento indexer facade
 *
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Processor
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
     * SmartDevs_ElastiCommerce_Model_Indexer_Processor constructor.
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
     * @param string $type
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
        //set current store scope to type indexer
        $this->indexerTypeInstances[$type]->setStore($this->getStore());
        $this->indexerTypeInstances[$type]->setIndexerClient($this->getIndexerClient());
        return $this->indexerTypeInstances[$type];
    }

    /**
     * get indexer client instance
     *
     * @return SmartDevs_ElastiCommerce_Indexer
     */
    protected function getIndexerClient()
    {
        if (false === isset($this->indexerClient[$this->getStoreId()])) {
            $this->indexerClient[$this->getStoreId()] = Mage::helper('elasticommerce/factory')->getIndexer($this->getStoreId());
        }
        return $this->indexerClient[$this->getStoreId()];
    }

    /**
     * set current store scope
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Processor
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
     * register document Types
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Processor
     */
    protected function registerDocumentTypes()
    {
        foreach ($this->getAllIndexerTypes() as $type) {
            $this->getIndexerClient()->registerDocumentType($type);
            $this->getIndexerTypeInstance($type)->addTypeMapping();
        }
        return $this;
    }

    public function reindex($entityId, $type)
    {
        /** @var SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract $indexerTypeInstance */
        $indexerTypeInstance = $this->getIndexerTypeInstance($type);
        
        try {
            $document = $indexerTypeInstance->reindexStore($entityId, $entityId);
        }catch (\Error $e){
            if($e instanceof TypeError){
                return $this;
            }
        }
    }

    public function reindexMultiple($min, $max, $type)
    {
        /** @var SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract $indexerTypeInstance */
        $indexerTypeInstance = $this->getIndexerTypeInstance($type);

        try {
            $document = $indexerTypeInstance->reindexStore($min, $max);
        }catch (\Error $e){
            if($e instanceof TypeError){
                return $this;
            }
        }
    }

    /**
     * Rebuild ElastiCommerce Index for all stores
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Processor
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
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Processor
     */
    public function rebuild($store = null)
    {
        //set current store scope
        $this->setStore($store);
        //set flag current process is full reindexing
        $this->getIndexerClient()->setIsFullReindex(true);
        Mage::dispatchEvent('elasticommerce_rebuild_store_before', array('indexer' => $this, 'store' => $this->getStore()));
        //register document types and mapping at indexer
        $this->registerDocumentTypes();
        //create new index
        $this->getIndexerClient()->createIndex();
        //reindex data for indexer_types
        foreach ($this->getAllIndexerTypes() as $type) {
            $this->getIndexerTypeInstance($type)->reindexStore();
        }
        // rotate index alias
        $this->getIndexerClient()->rotateIndex();
        Mage::dispatchEvent('elasticommerce_rebuild_store_after', array('indexer' => $this, 'store' => $this->getStore()));
        //reset flag current process is full reindexing
        $this->getIndexerClient()->setIsFullReindex(false);
        return $this;
    }
}