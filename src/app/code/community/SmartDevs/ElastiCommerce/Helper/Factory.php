<?php
declare(strict_types=1);

use SmartDevs\ElastiCommerce\Config\{
    IndexConfig, ServerConfig
};
use SmartDevs\ElastiCommerce\Index\Indexer;

/**
 * elasticommerce module factory helper
 */
class SmartDevs_ElastiCommerce_Helper_Factory
{

    /**
     * @var SmartDevs_ElastiCommerce_Indexer[]
     */
    protected $indexer = [];

    /**
     * get new instance of ServerConfig
     *
     * @param int $storeId
     * @return ServerConfig
     */
    protected function createServerConfig(int $storeId): ServerConfig
    {
        return new SmartDevs_ElastiCommerce_Model_Config_ServerConfig($storeId);
    }

    /**
     * get new instance of ServerConfig
     *
     * @param int $storeId
     * @return IndexConfig
     */
    protected function createIndexConfig(int $storeId): IndexConfig
    {
        $config = new SmartDevs_ElastiCommerce_Model_Config_IndexConfig($storeId);

        return $config;
    }

    /**
     * create new indexer Instance
     *
     * @param int $storeId
     * @return SmartDevs_ElastiCommerce_Indexer
     */
    protected function createIndexerInstance(int $storeId): SmartDevs_ElastiCommerce_Indexer
    {
        return new SmartDevs_ElastiCommerce_Indexer(
            $this->createServerConfig($storeId),
            $this->createIndexConfig($storeId)
        );
    }

    /**
     * get an indexer instance
     *
     * @param int $storeId
     * @return SmartDevs_ElastiCommerce_Indexer
     */
    public function getIndexer(int $storeId): SmartDevs_ElastiCommerce_Indexer
    {
        if (false === isset($this->indexer[$storeId])) {
            $this->indexer[$storeId] = $this->createIndexerInstance($storeId);
        }
        return $this->indexer[$storeId];
    }
}