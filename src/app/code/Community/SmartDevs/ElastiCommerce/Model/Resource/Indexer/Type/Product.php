<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 14.02.17
 * Time: 15:18
 */
class SmartDevs_ElastiCommerce_Model_Resource_Indexer_Type_Product extends SmartDevs_ElastiCommerce_Model_Resource_Indexer_Type_Abstract
{
    const TMP_TABLE_NAME = 'elasticommerce_product_status';

    /**
     * Initialize connection
     * @SuppressWarnings("PHPMD.CamelCaseMethodName")
     */
    protected function _construct()
    {
        $this->_init('catalog/product', 'entity_id');
    }

    /**
     * @return string
     */
    protected function getStatusFilterTableName()
    {
        return self::TMP_TABLE_NAME;
    }

    /**
     * get product range for current store
     *
     * @param int $websiteId
     * @return array
     */
    public function getProductRange($websiteId)
    {
        /** @var Varien_Db_Select $select */
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
     * prepare prefiltered table with status etc
     *
     * @param int $websiteId
     * @param array $productIds
     * @return $this
     */
    public function prepareProductPreFilter($websiteId, array $productIds)
    {
        // create temp table for faster joins
        if (true === $this->_getWriteAdapter()->isTableExists($this->getStatusFilterTableName())) {
            $this->_getWriteAdapter()->dropTable($this->getStatusFilterTableName());
        }
        $table = new Varien_Db_Ddl_Table();
        $table->setName($this->getStatusFilterTableName());
        $table->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary' => true,
        ), 'Entity ID');
        $this->_getWriteAdapter()->createTable($table);
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getModel('catalog/product')->getCollection();
        $collection->addWebsiteFilter($websiteId);
        $collection->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', array('eq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH));
        $collection->getSelect()->reset(Varien_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array('e.entity_id'));
        if (true === isset($productIds['from']) && true === isset($productIds['to'])) {
            $collection->getSelect()->where('e.entity_id >= ? ', (int)$productIds['from']);
            $collection->getSelect()->where('e.entity_id <= ? ', (int)$productIds['to']);
        } else if (true === is_array($productIds)) {
            $collection->getSelect()->where('e.entity_id IN (?)', array_map('intval', $productIds['in']));
        }
        $insertQuery = $this->_getWriteAdapter()->insertFromSelect($collection->getSelect(), $this->getStatusFilterTableName(), array('entity_id'));
        $this->_getWriteAdapter()->query($insertQuery);
        return $this;
    }

    /**
     * get default static attribute values
     *
     * @param $websiteId
     * @return mixed
     */
    public function getDefaultProductAttributeValues($websiteId)
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
        // FROM `catalog_product_entity` AS `e`
        //   INNER JOIN `elasticommerce_product_status` AS `status` ON e.entity_id = status.entity_id
        // WHERE
        // ORDER BY NULL
        $select->from(
            ['e' => $this->getTable('catalog/product'),
                [
                    'e.entity_id',
                    'e.entity_type_id',
                    'e.attribute_set_id',
                    'e.type_id',
                    'e.created_at',
                    'e.updated_at',
                    'e.sku'
                ]
            ]
        );
        $select->joinInner(['status' => $this->getStatusFilterTableName()], 'e.entity_id = status.entity_id', null);
        $select->order(new Zend_Db_Expr('NULL')); //ORDER BY NULL to avoid unnecessary sorting
        return array_reduce($this->_getWriteAdapter()->query($select)->fetchAll(), function ($result, $row) {
            $result[$row['entity_id']] = $row;
            return $result;
        }, []);
    }

    /**
     * receive product to category relation information
     *
     * @param int $websiteId
     * @param int $storeId
     * @param array $productIds
     */
    public function getProductToCategoryRelations($websiteId, $storeId, array $productIds)
    {
        $this->_getWriteAdapter()->query('SET SESSION group_concat_max_len = 10486808576;');
        /** @var Varien_Db_Select $select */
        $select = $this->getSelect();
        // SELECT
        //     `e`.`product_id` AS `entity_id`,
        //     GROUP_CONCAT(IF(e.is_parent = 1, e.category_id, '') SEPARATOR ';') AS `categories`,
        //     GROUP_CONCAT(IF(e.is_parent = 0, e.category_id, '') SEPARATOR ';') AS `anchors`,
        //     GROUP_CONCAT(CONCAT(e.category_id, '_', e.position) SEPARATOR ';') AS `sort`
        // FROM `catalog_product_website` AS `wp`
        //      INNER JOIN `elasticommerce_product_status` AS `status` ON wp.product_id = status.entity_id
        //      INNER JOIN `catalog_category_product_index` AS `e` ON wp.product_id = e.product_id AND (e.store_id = 1)
        // WHERE
        //     (wp.product_id >= 231 ) AND (wp.product_id <= 3333333 )
        //     AND (wp.website_id = 1)
        // GROUP BY `wp`.`product_id`
        // ORDER BY NULL
        $select->from(array('wp' => $this->getTable('catalog/product_website')), null)
            ->join(array('status' => $this->getStatusFilterTableName()), 'wp.product_id = status.entity_id', null)
            ->join(
                array('e' => $this->getTable('catalog/category_product_index')),
                'wp.product_id = e.product_id',
                array(
                    'entity_id' => 'product_id',
                    'categories' => new Zend_Db_Expr("GROUP_CONCAT(IF(e.is_parent = 1, e.category_id, '') SEPARATOR ';')"),
                    'anchors' => new Zend_Db_Expr("GROUP_CONCAT(IF(e.is_parent = 0, e.category_id, '') SEPARATOR ';')"),
                    'sort' => new Zend_Db_Expr("GROUP_CONCAT(CONCAT(e.category_id, '_', e.position) SEPARATOR ';')"),
                ));
        if (true === isset($productIds['from']) && true === isset($productIds['to'])) {
            $select->where('wp.product_id >= ? ', (int)$productIds['from']);
            $select->where('wp.product_id <= ? ', (int)$productIds['to']);
        } else if (true === is_array($productIds)) {
            $select->where('wp.product_id IN (?)', array_map('intval', $productIds['in']));
        }
        $select->where('wp.website_id = ?', (int)$websiteId);
        $select->where('e.store_id = ?', $storeId);
        $select->group('wp.product_id');
        // ORDER BY NULL to avoid second sorting run with filesort on disc
        // sorting is already done within group by
        $select->order(new Zend_Db_Expr('NULL'));
        $return = array();
        foreach ($this->_getWriteAdapter()->query($select)->fetchAll() as $productRow) {
            $productId = $productRow['entity_id'];
            $return[$productId] = $this->processProductToCategoryRelationsResponse($productRow);
        }
        return $return;
    }

    /**
     * Processing the data for productRow in method getProductToCategoryRelations
     *
     * @param array $productRow
     * @return array
     */
    protected function processProductToCategoryRelationsResponse(array $productRow)
    {
        //get all categories for product
        $categories = array();
        foreach (array_values(array_filter(explode(';', $productRow['categories']))) as $categoryId) {
            $categories[] = (int)$categoryId;
        }
        //get all anchors where product is visible
        $anchors = array();
        foreach (array_values(array_filter(explode(';', $productRow['anchors']))) as $categoryId) {
            $anchors[] = (int)$categoryId;
        }
        //get sort order for all categories and anchors
        $sortOrder = array();
        foreach (explode(';', $productRow['sort']) as $productSort) {
            list($categoryId, $position) = explode('_', $productSort);
            $sortOrder['category_' . $categoryId] = (int)$position;
        }
        return array(
            'categories' => $categories,
            'anchors' => $anchors,
            'sort' => $sortOrder,
        );
    }

    public function getStaticAttributeValues()
    {

    }

#    public function getAttributeValues()
#    {#
#
#    }

    public function getOptionValues()
    {

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
        //   INNER JOIN `elasticommerce_product_status` AS `status` ON wp.product_id = status.entity_id
        //   INNER JOIN `catalog_product_entity` AS `e` ON wp.product_id = e.entity_id
        // WHERE
        //   (wp.product_id >= 15006 ) AND (wp.product_id <= 16000 ) AND (wp.website_id = 1)
        // ORDER BY NULL
        $select->from(array('wp' => $this->getTable('catalog/product_website')), null)
            ->join(array('status' => $this->getStatusFilterTableName()), 'wp.product_id = status.entity_id', null)
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
     * @param int $websiteId
     * @param int $storeId
     * @param array $productIds
     * @return array
     * @throws Zend_Db_Select_Exception
     */
    public function getAttributeValues($attribute, $storeId)
    {
        // we don't deal here with static stuff
        if ($attribute->getBackendType() == Mage_Eav_Model_Attribute::TYPE_STATIC) {
            return [];
        }
        //empty attributes are not processed
        if (count($attribute->getFlatColumns()) == 0) {
            return [];
        }
        /** @var Varien_Db_Select $select */
        $select = $this->getSelect();
        // SELECT SQL_NO_CACHE
        //   `e`.`entity_id`,
        //  FROM `elasticommerce_product_status` AS `e`
        // ORDER BY NULL
        $select->from(['e' => $this->getStatusFilterTableName()], ['entity_id']);
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