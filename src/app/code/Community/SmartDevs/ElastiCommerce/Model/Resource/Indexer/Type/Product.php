<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 14.02.17
 * Time: 15:18
 */
class SmartDevs_ElastiCommerce_Model_Resource_Indexer_Type_Product extends SmartDevs_ElastiCommerce_Model_Resource_Indexer_Type_Abstract
{

    /**
     * Initialize connection
     * @SuppressWarnings("PHPMD.CamelCaseMethodName")
     */
    protected function _construct()
    {
        $this->_init('catalog/product', 'entity_id');
    }

    /**
     * get product range for current store
     *
     * @param int $websiteId
     * @return array
     */
    public function getProductRange($websiteId)
    {
        /** @var Elasticgento_Catalog_Model_Resource_Mysql_Select $select */
        $select = $this->getSelect();
        // SELECT SQL_NO_CACHE
        //   min(e.entity_id) AS `start`,
        //   max(e.entity_id) AS `end`
        // FROM `catalog_product_entity` AS `e`
        //   INNER JOIN `catalog_product_website` AS `wp` ON e.entity_id = wp.product_id AND wp.website_id = :website_id
        // ORDER BY NULL
        // LIMIT 1
        $select->from(array('e' => $this->getTable('catalog/product')),
            array('start' => new Zend_Db_Expr('min(e.entity_id)'), 'end' => new Zend_Db_Expr('max(e.entity_id)')))
            ->join(
                array('wp' => $this->getTable('catalog/product_website')),
                'e.entity_id = wp.product_id AND wp.website_id = :website_id',
                array())
            ->limit(1)
            ->order(new Zend_Db_Expr('NULL')); //ORDER BY NULL to avoid unnecessary sorting
        return $this->_getReadAdapter()->query($select, array('website_id' => $websiteId))->fetch();
    }
}