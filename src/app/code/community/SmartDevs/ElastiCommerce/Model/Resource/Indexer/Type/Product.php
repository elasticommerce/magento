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
        $collection->getSelect()->joinLeft(['cpsl' => $this->getTable('catalog/product_super_link')], 'e.entity_id = cpsl.product_id');
        $collection->getSelect()->joinInner(['cp_stst' => $this->getTable('cataloginventory/stock_status')],
            sprintf('e.entity_id = cp_stst.product_id AND cp_stst.website_id = %u AND cp_stst.stock_id = 1 AND cp_stst.stock_status = %u', $websiteId, Mage_CatalogInventory_Model_Stock::STOCK_IN_STOCK),
            null
        );
        #$collection->addAttributeToFilter('visibility', array('nin' => array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)));
        $collection->getSelect()->reset(Varien_Db_Select::COLUMNS);
        $collection->getSelect()->columns(array('e.entity_id'));
        $collection->getSelect()->where('cpsl.product_id IS NULL');
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
     * @param int $websiteId
     * @param int $storeId
     * @return mixed
     */
    public function getDefaultProductAttributeValues($websiteId, $storeId)
    {
        $statusAttribute = Mage::getSingleton("eav/config")->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'status');
        $visibilityAttribute = Mage::getSingleton("eav/config")->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'visibility');
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
                    'e.sku',
                ]
            ]
        );
        #$select->columns(
        #    [
        #        'status' => new Zend_Db_Expr('COALESCE(at_status_store.value, at_status_default.value)'),
        #        'visibility' => new Zend_Db_Expr('COALESCE(at_visibility_store.value, at_visibility_default.value)')
        #
        #    ]);
        $select->joinInner(['status' => $this->getStatusFilterTableName()], 'e.entity_id = status.entity_id', null);
        #$select->joinInner(['at_status_default' => $statusAttribute->getBackendTable()],
        #    sprintf('(`at_status_default`.`entity_id` = `e`.`entity_id`) AND (`at_status_default`.`attribute_id` = %u) AND `at_status_default`.`store_id` = 0', $statusAttribute->getId()),
        #    null
        #);
        #$select->joinLeft(['at_status_store' => $statusAttribute->getBackendTable()],
        #    sprintf('(`at_status_store`.`entity_id` = `e`.`entity_id`) AND (`at_status_store`.`attribute_id` = %u) AND `at_status_store`.`store_id` = %u', $statusAttribute->getId(), $storeId),
        #    null
        #);
        #$select->joinInner(['at_visibility_default' => $statusAttribute->getBackendTable()],
        #    sprintf('(`at_visibility_default`.`entity_id` = `e`.`entity_id`) AND (`at_visibility_default`.`attribute_id` = %u) AND `at_visibility_default`.`store_id` = 0', $visibilityAttribute->getId()),
        #    null
        #);
        #$select->joinLeft(['at_visibility_store' => $statusAttribute->getBackendTable()],
        #    sprintf('(`at_visibility_store`.`entity_id` = `e`.`entity_id`) AND (`at_visibility_store`.`attribute_id` = %u) AND `at_visibility_store`.`store_id` =  %u', $visibilityAttribute->getId(), $storeId),
        #    null
        #);
        #$select->joinInner(['cp_stst' => $this->getTable('cataloginventory/stock_status')],
        #    sprintf('e.entity_id = cp_stst.product_id AND cp_stst.website_id = %u AND cp_stst.stock_id = 1', $websiteId),
        #    ['stock_qty' => 'qty', 'stock_status']
        #);
        $select->order(new Zend_Db_Expr('NULL')); //ORDER BY NULL to avoid unnecessary sorting
        return array_reduce($this->_getWriteAdapter()->query($select)->fetchAll(), function ($result, $row) {
            $result[$row['entity_id']] = $row;
            return $result;
        }, []);
    }

    /**
     * receive product to category relation information
     *
     * @param int $storeId
     * @return array
     */
    public function getProductToCategoryRelations($storeId)
    {
        $this->_getWriteAdapter()->query('SET SESSION group_concat_max_len = 10486808576;');
        /** @var Varien_Db_Select $select */
        $select = $this->getSelect();
        // SELECT
        //     `e`.`entity_id`,
        //     GROUP_CONCAT(IF(e.is_parent = 1, e.category_id, '') SEPARATOR ';') AS `categories`,
        //     GROUP_CONCAT(IF(e.is_parent = 0, e.category_id, '') SEPARATOR ';') AS `anchors`,
        //     GROUP_CONCAT(CONCAT(e.category_id, '_', e.position) SEPARATOR ';') AS `sort`
        // FROM `elasticommerce_product_status` AS `wp`
        //      INNER JOIN `catalog_category_product_index` AS `cpi` ON e.entity_id = cpi.product_id AND (cpi.store_id = 1)
        // GROUP BY `wp`.`product_id`
        // ORDER BY NULL
        $select->from(['e' => $this->getStatusFilterTableName()], ['entity_id'])
            ->join(
                ['cpi' => $this->getTable('catalog/category_product_index')],
                'e.entity_id = cpi.product_id',
                [
                    'categories' => new Zend_Db_Expr("GROUP_CONCAT(IF(cpi.is_parent = 1, cpi.category_id, '') SEPARATOR ';')"),
                    'anchors' => new Zend_Db_Expr("GROUP_CONCAT(IF(cpi.is_parent = 0, cpi.category_id, '') SEPARATOR ';')"),
                    'sort' => new Zend_Db_Expr("GROUP_CONCAT(CONCAT(cpi.category_id, '_', cpi.position) SEPARATOR ';')"),
                ]
            );
        $select->where('cpi.store_id = ?', $storeId);
        $select->group('e.entity_id');
        // ORDER BY NULL to avoid second sorting run with filesort on disc
        // sorting is already done within group by
        $select->order(new Zend_Db_Expr('NULL'));
        return array_reduce($this->_getWriteAdapter()->query($select)->fetchAll(), function ($result, $row) {
            $result[$row['entity_id']] = array_diff_key($row, ['entity_id' => true]);
            return $result;
        }, []);
    }

    /**
     * get product variants
     *
     * @param $storeId
     * @return array
     */
    public function getProductVariants($storeId)
    {
        $this->_getWriteAdapter()->query('SET SESSION group_concat_max_len = 10486808576;');
        /** @var Varien_Db_Select $select */
        $select = $this->getSelect();
        $select->from(['e' => $this->getStatusFilterTableName()], ['entity_id'])
            ->join(
                ['cpsl' => $this->getTable('catalog/product_super_link')],
                'e.entity_id = cpsl.parent_id',
                [
                    'variant_id' => 'product_id',
                ]
            )
            ->join(['cpsua' => $this->getTable('catalog/product_super_attribute')],
                'e.entity_id = cpsua.product_id',
                null
            )
            ->join(['cpe' => $this->getTable('catalog/product')],
                'cpsl.product_id = cpe.entity_id',
                ['sku']
            )
            ->join(['eav' => $this->getTable('eav/attribute')],
                'eav.attribute_id = cpsua.attribute_id',
                null
            )
            ->join(['cpei' => $this->getTable('catalog/product') . '_int'],
                'cpsl.product_id = cpei.entity_id AND cpei.attribute_id = cpsua.attribute_id and cpei.store_id = 0',
                null
            );
        $select->columns(['variation' => new Zend_Db_Expr('GROUP_CONCAT( DISTINCT CONCAT(eav.attribute_code, \':\', cpei.value) ORDER BY cpsua.position SEPARATOR "|")')]);
        $select->group(['e.entity_id', 'cpsl.product_id']);
        // ORDER BY NULL to avoid second sorting run with filesort on disc
        // sorting is already done within group by
        $select->order(new Zend_Db_Expr('NULL'));
        return $this->_getWriteAdapter()->query($select)->fetchAll();
    }

    /**
     * @param $websiteId
     * @return array
     */
    public function getProductStockStatus($websiteId)
    {
        $select = $this->getSelect();
        $select->from(['e' => $this->getStatusFilterTableName()], ['entity_id'])
            ->join(['stst' => $this->getTable('cataloginventory/stock_status')],
                sprintf('e.entity_id = stst.product_id AND stst.website_id = %u AND stst.stock_id = 1', $websiteId),
                ['qty', 'stock_status']
            );
        $select->order(new Zend_Db_Expr('NULL'));

        return array_reduce($this->_getWriteAdapter()->query($select)->fetchAll(), function ($result, $row) {
            $result[$row['entity_id']] = ['qty' => $row['qty'], 'stock_status' => $row['stock_status']];
            return $result;
        }, []);
    }

    /**
     * @param $websiteId
     * @return array
     */
    public function getProductPrices($websiteId)
    {
        $select = $this->getSelect();
        $select->from(['e' => $this->getStatusFilterTableName()], ['entity_id'])
            ->join(['cpp' => $this->getTable('catalog/product_index_price')],
                sprintf('e.entity_id = cpp.entity_id AND cpp.customer_group_id >= 0 AND cpp.website_id = %u', $websiteId),
                ['prices' => new Zend_Db_Expr("GROUP_CONCAT(CONCAT(cpp.customer_group_id, ';', cpp.final_price) SEPARATOR '|')")]
            );
        $select->group('e.entity_id');
        $select->order(new Zend_Db_Expr('NULL'));

        return array_reduce($this->_getWriteAdapter()->query($select)->fetchAll(), function ($result, $row) {
            $result[$row['entity_id']] = $row['prices'];
            return $result;
        }, []);
    }

    /**
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param int $storeId
     * @param array $optionIds
     *
     * @return array
     */
    public function getOptionValues($attribute, $storeId, $optionIds = array())
    {
        /** @var Varien_Db_Select $select */
        $select = $this->getSelect();
        $select->from(['eav' => $this->getTable('eav/attribute_option')], ['option_id']);
        $select->joinInner(['tdv' => $this->getTable('eav/attribute_option_value')], 'tdv.option_id = eav.option_id AND tdv.store_id = 0', null);
        $select->joinLeft(['tsv' => $this->getTable('eav/attribute_option_value')], 'tsv.option_id = eav.option_id AND tsv.store_id = ' . $storeId, null);
        $select->where('eav.attribute_id = ?', $attribute->getAttributeId());
        $select->columns(['label' => new Zend_Db_Expr('COALESCE(tsv.value, tdv.value)')]);
        if (true === is_array($optionIds) && count($optionIds) > 0) {
            $select->where('eav.option_id IN (?)', array_map('intval', $optionIds));
        }
        return $this->_getWriteAdapter()->query($select)->fetchAll(PDO::FETCH_KEY_PAIR);
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