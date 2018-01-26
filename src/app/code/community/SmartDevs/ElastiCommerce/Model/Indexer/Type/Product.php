<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:41
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Type_Product
    extends SmartDevs_ElastiCommerce_Model_Indexer_Type_AbstractEntity
    implements SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface
{

    protected $_systemAttributes = array('status', 'required_options', 'tax_class_id', 'weight');
    /**
     * indexer type string
     *
     * @var string
     */
    protected static $indexerType = 'product';

    /**
     * get current entity type code
     *
     * @return string
     */
    protected function getEntityTypeCode()
    {
        return Mage_Catalog_Model_Product::ENTITY;
    }

    protected function getChunkSize()
    {
        return intval(Mage::getStoreConfig('elasticommerce/index/chunk_size', $this->getStoreId()));
    }

    /**
     * get Resource
     *
     * @return SmartDevs_ElastiCommerce_Model_Resource_Indexer_Type_Product
     */
    protected function getResourceModel()
    {
        return Mage::getResourceSingleton('elasticommerce/indexer_type_product');
    }

    /**
     * get Product chunks for reindex
     *
     * @return array
     */
    protected function getProductChunks()
    {
        $range = $this->getResourceModel()->getProductRange($this->getWebsiteId());
        $chunks = $this->getChunksByRange((int)$range['start'], (int)$range['end'], $this->getChunkSize());
        Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Reindexing %u chunks', count($chunks)));
        return $chunks;
    }

    /**
     * prepare product prefilter table for faster indexing speed
     *
     * @param array $productIds
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Product
     */
    protected function prepareProductPreFilter(array $productIds)
    {
        $this->getResourceModel()->prepareProductPreFilter($this->getWebsiteId(), $productIds);
        return $this;
    }

    /**
     * creates index documents and attach it to the Document Collection in indexer Client
     *
     * @param array $chunk
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Product
     */
    protected function createIndexDocuments()
    {
        $rawData = $this->getResourceModel()->getDefaultProductAttributeValues($this->getWebsiteId());
        foreach ($rawData as $id => $rawData) {
            $document = $this->createNewDocument((int)$id);
            $document->addResultData($rawData);
            $this->getBulkCollection()->addItem($document);
        }
        return $this;
    }

    /**
     * get all select / Multiselect options
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @return array
     */
    protected function getAttributeOptions(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        if ($attribute->getFrontend()->getInputType() === 'select' || $attribute->getFrontend()->getInputType() === 'multiselect') {
            return $this->getResourceModel()->getOptionValues($attribute, $this->getStoreId());
        }
        return [];
    }

    /**
     * add attribute data to documents
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param $chunk
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Product
     */
    protected function addAttributeDataToDocuments(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        $attributeRawData = $this->getResourceModel()->getAttributeValues($attribute, $this->getStoreId());
        if ($attribute->getFrontend()->getInputType() === 'multiselect') {
            $attributeOptions = $this->getAttributeOptions($attribute);
        } else {
            $attributeOptions = [];
        }
        foreach ($attributeRawData as $id => $values) {
            /** @var SmartDevs_ElastiCommerce_IndexDocument $document */
            $document = $this->getDocument($this->getDocumentId($id));
            $document->addResultData($values);
            if ($attribute->getAttributeCode() === 'visibility') {
                $document->setVisibility((int)$values[$attribute->getAttributeCode()]);
                continue;
            }
            if ($attribute->getAttributeCode() === 'status') {
                $document->setStatus((int)$values[$attribute->getAttributeCode()]);
                continue;
            }
            //add attribute to sort
            if (true === boolval($attribute->getUsedForSortBy())) {
                $document->addSortString($attribute->getSortColumnField(), $values[$attribute->getSortColumnField()], $attribute->getSortFieldType());
            }
            // add filterable attribute data
            if (true === boolval($attribute->getIsFilterable())) {
                if ($attribute->getFrontend()->getInputType() === 'select' || $attribute->getFrontend()->getInputType() === 'multiselect') {
                    $document->addFilterNumeric($attribute->getAttributeCode(), array_map('intval', explode(',', $values[$attribute->getAttributeCode()])), \SmartDevs\ElastiCommerce\Index\Document::FILTER_NUMBER);
                } else {
                    $document->addFilterString($attribute->getAttributeCode(), $values[$attribute->getAttributeCode()]);
                }
            }
            //handle multiselect values
            switch (true) {
                case $attribute->getFrontend()->getInputType() === 'multiselect' && true === (bool)$attribute->getIsUsedForCompletion():
                case $attribute->getFrontend()->getInputType() === 'multiselect' && true === (bool)$attribute->getIsSearchable():
                case $attribute->getFrontend()->getInputType() === 'multiselect' && true === (bool)$attribute->getIsUsedForBoostedSearch():
                    {
                        $attributeValues = [];
                        foreach (explode(',', $values[$attribute->getAttributeCode()]) as $optionId) {
                            $attributeValues[] = $attributeOptions[$optionId];
                        }
                        $document->addResultData([sprintf('%s_values', $attribute->getAttributeCode()) => implode(' ', $attributeValues)]);
                        break;
                    }
            }
        }
        return $this;
    }

    /**
     * add product to category relations
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Product
     */
    protected function addCategoryRelations()
    {
        $result = $this->getResourceModel()->getProductToCategoryRelations($this->getStoreId());
        foreach ($result as $id => $data) {
            $document = $this->getDocument($this->getDocumentId($id));
            foreach (array_values(array_filter(explode(';', $data['sort']))) as $sort) {
                list($categoryId, $position) = explode('_', $sort);
                $document->addSortNumeric('category_' . $categoryId, (int)$position);
            }
            $document->setCategories(array_map('intval', array_values(array_filter(explode(';', $data['categories'])))));
            $document->setAnchors(array_map('intval', array_values(array_filter(explode(';', $data['anchors'])))));
        }
        return $this;
    }

    /**
     * extend the default mapping for different actions
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Product
     */
    public function addTypeMapping()
    {
        $mapping = $this->getResultFieldMapping();
        foreach ($this->getEntityAttributes() as $attribute) {
            $columns = $attribute->getFlatColumns();
            if (false === array_key_exists(sprintf('%s_value', $attribute->getAttributeCode()), $columns) && $attribute->getFrontend()->getInputType() !== 'multiselect') {
                $valueColumn = $attribute->getAttributeCode();
            } elseif ($attribute->getFrontend()->getInputType() !== 'multiselect' && array_key_exists(sprintf('%s_value', $attribute->getAttributeCode()), $columns)) {
                $valueColumn = sprintf('%s_value', $attribute->getAttributeCode());
            } elseif ($attribute->getFrontend()->getInputType() === 'multiselect') {
                $valueColumn = sprintf('%s_values', $attribute->getAttributeCode());
            } else {
                $valueColumn = $attribute->getAttributeCode();
            }
            if (true === (bool)$attribute->getIsSearchable()) {
                $mapping->getCollection()->getField($valueColumn, 'keyword')->setCopyTo('fulltext');
            }
            if (true === (bool)$attribute->getIsUsedForBoostedSearch()) {
                $mapping->getCollection()->getField($valueColumn, 'keyword')->setCopyTo('fulltext_boosted');
            }
            if (true === (bool)$attribute->getIsUsedForCompletion()) {
                $mapping->getCollection()->getField($valueColumn, 'keyword')->setCopyTo('completion');
            }
        }
        return $this;
    }

    /**
     * reindex complete store
     *
     * @param Mage_Core_Model_Store $store
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Product
     */
    public function reindexStore()
    {
        $storeTimeStart = microtime(true);
        foreach ($this->getProductChunks() as $chunk) {
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Reindexing product chunk %u - %u', $chunk['from'], $chunk['to']));
            // prepare filter table for faster prefiltered queries
            $this->prepareProductPreFilter($chunk);
            $timeStart = microtime(true);
            $this->createIndexDocuments($chunk);
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Prepared chunk Documents in %.4f seconds', microtime(true) - $timeStart));
            $timeStart = microtime(true);
            foreach ($this->getEntityAttributes() as $attribute) {
                $this->addAttributeDataToDocuments($attribute);
            }
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Added all attribute data to chunk in %.4f seconds', microtime(true) - $timeStart));
            $this->addCategoryRelations();
            $timeStart = microtime(true);
            $this->getIndexerClient()->sendBulk();
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Added chunk data in  %.4f seconds', microtime(true) - $timeStart));
        }
        Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Reindexed Store %u in  %.4f seconds', $this->getStoreId(), microtime(true) - $storeTimeStart));
        return $this;
    }
}