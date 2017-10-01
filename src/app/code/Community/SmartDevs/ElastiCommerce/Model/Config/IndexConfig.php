<?php
declare(strict_types = 1);

use SmartDevs\ElastiCommerce\Config\IndexConfig;


/**
 * Class SmartDevs_ElastiCommerce_Model_Config_IndexConfig
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
final class SmartDevs_ElastiCommerce_Model_Config_IndexConfig extends IndexConfig
{
    /**
     * xml path to number of shards configuration
     */
    const XML_CONFIG_PATH_INDEX_SHARDS = 'elasticommerce/index/number_of_shards';

    /**
     * xml path to number of replicas configuration
     */
    const XML_CONFIG_PATH_INDEX_REPLICAS = 'elasticommerce/index/number_of_replicas';

    /**
     * xml path to index prefix configuration
     */
    const XML_CONFIG_PATH_INDEX_PREFIX = 'elasticommerce/index/prefix';

    /**
     * xml path to index schema config file
     */
    const XML_CONFIG_PATH_INDEX_SCHEMA_CONFIG_FILE = 'elasticommerce/index/schema_config_file';

    /**
     * xml path to index analyzer config file
     */
    const XML_CONFIG_PATH_INDEX_ANALYZER_CONFIG_FILE = 'elasticommerce/index/analyzer_config_file';

    /**
     * index alia name schema with placeholders
     */
    const INDEX_ALIAS_NAME_SCHEMA = '%s_%s_%s';

    /**
     * index alia name schema with placeholders
     */
    const SCHEMA_CONFIG_FILE_NAME_SCHEMA = '%s%selasticommerce%s%s';

    /**
     * SmartDevs_ElastiCommerce_Model_Config_ServerConfig constructor.
     *
     * @param int $storeId
     */
    public function __construct(int $storeId)
    {
        $this->setNumberOfShards(intval(Mage::getStoreConfig(self::XML_CONFIG_PATH_INDEX_SHARDS, $storeId)));
        $this->setNumberOfReplicas(intval(Mage::getStoreConfig(self::XML_CONFIG_PATH_INDEX_REPLICAS, $storeId)));
        $this->setIndexAlias(sprintf(
                self::INDEX_ALIAS_NAME_SCHEMA,
                Mage::getStoreConfig(self::XML_CONFIG_PATH_INDEX_PREFIX),
                Mage::app()->getStore($storeId)->getWebsite()->getCode(),
                Mage::app()->getStore($storeId)->getCode()
            )

        );
        $this->setSchemaConfigFile(
            sprintf(self::SCHEMA_CONFIG_FILE_NAME_SCHEMA,
                Mage::getBaseDir('etc'),
                DS,
                DS,
                strval(Mage::getStoreConfig(self::XML_CONFIG_PATH_INDEX_SCHEMA_CONFIG_FILE, $storeId)))
        );
        $this->setAnalyzerConfigFile(
            sprintf(self::SCHEMA_CONFIG_FILE_NAME_SCHEMA,
                Mage::getBaseDir('etc'),
                DS,
                DS,
                strval(Mage::getStoreConfig(self::XML_CONFIG_PATH_INDEX_ANALYZER_CONFIG_FILE, $storeId)))
        );
    }
}