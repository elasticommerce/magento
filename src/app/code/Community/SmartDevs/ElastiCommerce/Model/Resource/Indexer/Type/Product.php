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

    /**
     * get all default attributes for complete reindex
     *
     * @param  int $websiteId
     * @param  array $productIds
     * @return array
     */
    public function getStaticProductAttributes($websiteId, array $productIds)
    {
        /** @var Varien_Db_Select $select */
        $select = $this->getSelect();
        // SELECT SQL_NO_CACHE
        //   `e`.`entity_id`,
        //   `e`.`type_id`,
        //   `e`.`attribute_set_id`
        //   `e`.`created_at`
        //   `e`.`updated_at`
        //   `e`.`sku`
        // FROM `catalog_product_website` AS `wp`
        //   INNER JOIN `catalog_product_entity` AS `e` ON wp.product_id = e.entity_id
        // WHERE
        //   (wp.product_id >= 15006 ) AND (wp.product_id <= 16000 ) AND (wp.website_id = 1)
        // ORDER BY NULL
        $select->from(array('wp' => $this->getTable('catalog/product_website')), null)
            ->join(
                array('e' => $this->getTable('catalog/product')),
                'wp.product_id = e.entity_id',
                array(
                    'e.entity_id',
                    'e.entity_type_id',
                    'e.attribute_set_id',
                    'e.type_id',
                    'e.created_at',
                    'e.updated_at',
                    'e.sku'
                ));
        if (true === isset($productIds['from']) && true === isset($productIds['to'])) {
            $select->where('wp.product_id >= ? ', (int)$productIds['from']);
            $select->where('wp.product_id <= ? ', (int)$productIds['to']);
        } else if (true === is_array($productIds)) {
            $select->where('wp.product_id IN (?)', array_map('intval', $productIds['in']));
        }
        $select->where('wp.website_id = ?', (int)$websiteId);
        $select->order(new Zend_Db_Expr('NULL'));//ORDER BY NULL to avoid unnecessary sorting
        return array_reduce($this->_getWriteAdapter()->query($select)->fetchAll(), function ($result, $row) {
            $result[$row['entity_id']] = array_diff_key($row, ['entity_id' => true]);
            return $result;
        }, []);
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param $websiteId
     * @param $storeId
     * @param array $productIds
     * @return array
     * @throws Zend_Db_Select_Exception
     */
    public function getEavAttributeValues($attribute, $websiteId, $storeId, array $productIds)
    {
        // we don't deal here with static stuff
        if ($attribute->getBackendType() == Mage_Eav_Model_Attribute::TYPE_STATIC) {
            return [];
        }
        //empty attributes are not processed
        if (count($attribute->getFlatColumns()) == 0) {
            return [];
        }
        Mage::log((string)$attribute->getAttributeCode(), null, 'attribute.log', true);
        /** @var Varien_Db_Select $select */
        $select = $this->getSelect();
        // SELECT SQL_NO_CACHE
        //   `e`.`entity_id`,
        //   `e`.`updated_at`
        // FROM FROM `catalog_product_website` AS `wp`
        //   INNER JOIN `catalog_product_entity` AS `e` ON wp.product_id = e.entity_id
        // WHERE
        //   (wp.product_id >= 15006 ) AND (wp.product_id <= 16000 ) AND (wp.website_id = 1)
        // ORDER BY NULL
        $select->from(['wp' => $this->getTable('catalog/product_website')], null)
            ->join(
                array('e' => $this->getTable('catalog/product')),
                'wp.product_id = e.entity_id',
                array('entity_id'));
        if (true === isset($productIds['from']) && true === isset($productIds['to'])) {
            $select->where('wp.product_id >= ? ', (int)$productIds['from']);
            $select->where('wp.product_id <= ? ', (int)$productIds['to']);
        } else if (true === is_array($productIds)) {
            $select->where('wp.product_id IN (?)', array_map('intval', $productIds['in']));
        }
        $select->where('wp.website_id = ?', (int)$websiteId);
        $select->order(new Zend_Db_Expr('NULL'));//ORDER BY NULL to avoid unnecessary sorting

        /** @var Mage_Eav_Model_Entity_Attribute_Abstract $attribute */
        foreach ($attribute->getFlatUpdateSelect($storeId)->getPart('from') as $alias => $join) {
            $select->joinLeft([$alias => $join['tableName']],
                $join['joinCondition'],
                null);
        }
        /** @var Mage_Eav_Model_Entity_Attribute_Abstract $attribute */
        foreach ($attribute->getFlatUpdateSelect($storeId)->getPart('columns') as $column) {
            $select->columns([$column[2] => $column[1]]);
            $select->orHaving(sprintf('%s IS NOT NULL', $column[2]));
        }
        return array_reduce($this->_getWriteAdapter()->query($select)->fetchAll(), function ($result, $row) {
            $result[$row['entity_id']] = array_diff_key($row, ['entity_id' => true]);
            return $result;
        }, []);
    }
}