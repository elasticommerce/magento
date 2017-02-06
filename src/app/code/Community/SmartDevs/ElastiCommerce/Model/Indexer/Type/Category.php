<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:41
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Type_Category
    extends SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
    implements SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface
{
    protected static $indexerType = 'category';

    /**
     * magento entity
     *
     * @var string
     */
    protected static $entity = Mage_Catalog_Model_Category::ENTITY;

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