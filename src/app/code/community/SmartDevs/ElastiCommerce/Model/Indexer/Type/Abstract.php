<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:25
 */
abstract class SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
{
    /**
     * sprintf schema for document id
     */
    const DOCUMENT_ID_SCHEMA = '%s_%u';

    /**
     * @var SmartDevs_ElastiCommerce_Indexer
     */
    protected $indexer = null;

    /**
     * @var Mage_Core_Model_Store
     */
    protected $store = null;

    /**
     * current store id
     *
     * @var integer
     */
    protected $storeId = null;

    /**
     * current website id
     *
     * @var int
     */
    protected $websiteId = null;

    /**
     * current store group id
     *
     * @var integer
     */
    protected $storeGroupId = null;

    /**
     * set current store scope
     *
     * @param Mage_Core_Model_Store $store
     */
    public function setStore(Mage_Core_Model_Store $store)
    {
        $this->store = $store;
        $this->storeId = $store->getId();
        $this->websiteId = $store->getWebsiteId();
        $this->storeGroupId = $store->getGroupId();
    }

    /**
     * get store id
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    public function setStoreId(int $storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * get website id
     *
     * @return int
     */
    public function getWebsiteId(): int
    {
        return $this->websiteId;
    }

    /**
     * set store group id
     *
     * @param int $websiteId
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    public function setStoreGroupId(int $storeGroupId)
    {
        $this->storeGroupId = $storeGroupId;
        return $this;
    }

    /**
     * get website id
     *
     * @return int
     */
    public function getStoreGroupId(): int
    {
        return $this->storeGroupId;
    }

    /**
     * set website id
     *
     * @param int $websiteId
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    public function setWebsiteId(int $websiteId)
    {
        $this->websiteId = $websiteId;
        return $this;
    }

    /**
     * @param SmartDevs_ElastiCommerce_Indexer $indexer
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    public function setIndexerClient(SmartDevs_ElastiCommerce_Indexer $indexer): SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
    {
        $this->indexer = $indexer;
        return $this;
    }

    /**
     * @return \SmartDevs\ElastiCommerce\Index\Indexer
     */
    public function getIndexerClient(): SmartDevs_ElastiCommerce_Indexer
    {
        return $this->indexer;
    }

    /**
     * @return \SmartDevs\ElastiCommerce\Index\BulkCollection
     */
    public function getBulkCollection(): \SmartDevs\ElastiCommerce\Index\BulkCollection
    {
        return $this->getIndexerClient()->getBulk();
    }

    /**
     * get document type
     *
     * @return string
     */
    public function getDocumentType()
    {
        return static::$indexerType;
    }

    /**
     * calculate chunks based of min / max and chunksize
     *
     * @param int $offsetStart
     * @param int $offsetEnd
     * @param int $chunksize
     * @return array
     */
    protected function getChunksByRange($offsetStart, $offsetEnd, $chunksize = 2500)
    {
        $total = $offsetEnd - $offsetStart;
        $chunksCount = ceil($total / $chunksize);
        $chunks = [];
        for ($i = 0; $i < $chunksCount; $i++) {
            $chunks[] = ['from' => intval($offsetStart + ($chunksize * $i)), 'to' => intval($offsetStart + (($chunksize * $i) + $chunksize - 1))];
        }
        return $chunks;
    }

    /**
     * get column mapping from sql to elasticommerce fields
     *
     * @param $type
     * @return string
     */
    protected function getColumnFieldType($type)
    {
        //put not default to top
        switch (true) {
            case strpos($type, 'smallint') === 0:
            case strpos($type, 'tinyint') === 0:
            case strpos($type, 'int') === 0: {
                return 'integer';
            }
            case strpos($type, 'decimal') === 0: {
                return 'double';
            }
            case strpos($type, 'datetime') === 0:
            case strpos($type, 'timestamp') === 0: {
                return 'date';
            }
            default: {
                return 'text';
            }
        }
    }

    /**
     * get document id for given id
     *
     * @param $id
     * @return string
     */
    public function getDocumentId($id)
    {
        return sprintf(self::DOCUMENT_ID_SCHEMA, $this->getDocumentType(), $id);
    }

    /**
     * @return \SmartDevs\ElastiCommerce\Index\Type\Mapping\Field\FieldCollection
     */
    protected function getFieldMapping()
    {
        return $this->getIndexerClient()->getTypeMapping($this->getDocumentType())->getMappingFields();
    }

    /**
     * @return \SmartDevs\ElastiCommerce\Index\Type\Mapping\Field\FieldCollection
     */
    protected function getResultFieldMapping()
    {
        return $this->getIndexerClient()->getTypeMapping($this->getDocumentType())->getMappingFields()->getItemById('result');
    }

    /**
     * create a new document
     *
     * @param int $id
     * @return SmartDevs_ElastiCommerce_IndexDocument
     */
    protected function createNewDocument(int $id, string $action = 'create')
    {
        return new SmartDevs_ElastiCommerce_IndexDocument($this->getDocumentId($id), $this->getDocumentType(), $action);
    }

    /**
     * get docu
     *
     * @param string $docId
     * @return SmartDevs_ElastiCommerce_IndexDocument
     */
    public function getDocument(string $docId): SmartDevs_ElastiCommerce_IndexDocument
    {
        $doc = $this->getBulkCollection()->getItemById($docId);
        // fixes Return value of SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract::getDocument() must be an instance of SmartDevs_ElastiCommerce_IndexDocument
        if ($doc instanceof SmartDevs_ElastiCommerce_IndexDocument) {
            return $doc;
        }
        return $this->createNewDocument((int)$docId);
    }

    /**
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    public function addTypeMapping()
    {
        return $this;
    }

    /**
     * reindex complete store
     *
     * @param Mage_Core_Model_Store store to reindex
     * @return $this
     */
    public function reindexStore()
    {
        return $this;
    }

    /**
     * full reindex of complete entities
     *
     * @param $entityIds array entity ids to reindex
     * @param Mage_Core_Model_Store store to reindex
     *
     * @return $this
     */
    public function reindexEntity(array $entityIds): SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
    {
        return $this;
    }

    /**
     * full reindex of complete attributes
     *
     * @param $attributeCodes array attribute codes to reindex
     * @param Mage_Core_Model_Store store to reindex
     *
     * @return $this
     */
    public function reindexAttributes(array $attributeCodes): SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
    {
        return $this;
    }

    /**
     * partwise reindex of entities and attributes
     *
     * @param $entityIds array entity ids to reindex
     * @param $attributeCodes array attribute code to reindex
     * @param Mage_Core_Model_Store store to reindex
     *
     * @return $this
     */
    public function reindexEntityAttributes(array $entityIds, array $attributeCodes): SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
    {
        return $this;
    }
}
