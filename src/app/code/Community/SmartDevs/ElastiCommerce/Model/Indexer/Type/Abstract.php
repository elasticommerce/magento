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