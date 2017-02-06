<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:41
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Type_Product
    extends SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
    implements SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface
{
    /**
     * indexer type string
     *
     * @var string
     */
    protected static $indexerType = 'product';

    /**
     * magento entity
     *
     * @var string
     */
    protected static $entity = Mage_Catalog_Model_Product::ENTITY;

    /**
     * @var Mage_Eav_Model_Entity_Type
     */
    protected $_entity = null;

    /**
     * @var Mage_Eav_Model_Entity_Attribute[]
     */
    protected $_entityAttributes = null;

    /**
     * @var Mage_Eav_Model_Entity_Attribute[]
     */
    protected $_entityAttributeLookup = null;

    /**
     * get current entity type code
     *
     * @return string
     */
    protected function getEntityTypeCode()
    {
        return self::$entity;
    }

}