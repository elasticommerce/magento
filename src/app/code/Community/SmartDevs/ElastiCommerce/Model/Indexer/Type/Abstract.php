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
     * get indexer type
     *
     * @return string
     */
    public function getIndexerType()
    {
        return self::$indexerType;
    }

    /**
     * get entity type
     *
     * @return Mage_Eav_Model_Entity_Type
     */
    protected function getEntity()
    {
        if (null === $this->_entity) {
            $this->_entity = Mage::getSingleton('eav/config')->getEntityType($this->getEntityTypeCode());
        }
        return $this->_entity;
    }

    /**
     * get all entity attributes
     *
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    public function getEntityAttributes()
    {
        if (null === $this->_entityAttributes) {
            //preload attribute codes
            $attributeCodes = Mage::getSingleton('eav/config')->getEntityAttributeCodes($this->getEntity());
            foreach ($attributeCodes as $attributeCode) {
                /** @var Mage_Eav_Model_Entity_Attribute $attribute */
                $attribute = Mage::getSingleton('eav/config')->getAttribute($this->getEntity(), $attributeCode);
                // check if exists source and backend model.
                // To prevent exception when some module was disabled
                $attribute->usesSource() && $attribute->getSource();
                $attribute->getBackend();
                $this->_entityAttributes[$attribute->getId()] = $attribute;
                $this->_entityAttributeLookup[$attributeCode] = $attribute->getId();
            }
        }
        return $this->_entityAttributes;
    }

    /**
     * calculate chunks based of min / max and chunksize
     *
     * @param int $offsetStart
     * @param int $offsetEnd
     * @param int $chunksize
     * @return array
     */
    protected function getChunksByRange($offsetStart, $offsetEnd, $chunksize = 1000)
    {
        $total = $offsetEnd - $offsetStart;
        $chunksCount = ceil($total / $chunksize);
        $chunks = array();
        for ($i = 0; $i < $chunksCount; $i++) {
            $chunks[] = array('from' => intval($offsetStart + ($chunksize * $i)), 'to' => intval($offsetStart + (($chunksize * $i) + $chunksize - 1)));
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
                return 'string';
            }
        }
    }

    /**
     * @return array
     */
    public function getTypeMapping()
    {
        return array();
    }

    /**
     * reindex complete store
     *
     * @param Mage_Core_Model_Store store to reindex
     * @return $this
     */
    public function reindexStore(Mage_Core_Model_Store $store)
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
    public function reindexEntity(array $entityIds, Mage_Core_Model_Store $store)
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
    public function reindexAttributes(array $attributeCodes, Mage_Core_Model_Store $store)
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
    public function reindexEntityAttributes(array $entityIds, array $attributeCodes, Mage_Core_Model_Store $store)
    {
        return $this;
    }
}