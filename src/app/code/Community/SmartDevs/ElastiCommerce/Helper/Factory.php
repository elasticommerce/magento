<?php
declare(strict_types = 1);

use SmartDevs\ElastiCommerce\Implementor\Config;
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
     * @var Config[]
     */
    protected $configs = [];

    /**
     * @var Indexer[]
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
     * @return ServerConfig
     */
    protected function createIndexConfig(int $storeId): IndexConfig
    {
        $config = new SmartDevs_ElastiCommerce_Model_Config_IndexConfig($storeId);

        return $config;
    }

    /**
     * create new config instance
     *
     * @param int $storeId
     * @return Config
     */
    public function createConfig(int $storeId): Config
    {
        $config = new SmartDevs_ElastiCommerce_Model_Config_Store();
        $config->setServerConfig($this->createServerConfig($storeId));
        $config->setIndexConfig($this->createIndexConfig($storeId));
        return $config;
    }

    /**
     * get config instance
     *
     * @param int $storeId
     * @return Config
     */
    public function getConfig(int $storeId): Config
    {
        if (false === isset($this->configs[$storeId])) {
            $this->configs[$storeId] = $this->createConfig($storeId);
        }
        return $this->configs[$storeId];
    }

    /**
     * get an indexer instance
     *
     * @param int $storeId
     * @return Indexer
     */
    public function getIndexer(int $storeId): Indexer
    {
        if (false === isset($this->indexer[$storeId])) {
            $this->indexer[$storeId] = $this->createIndexer($storeId);
        }
        return $this->indexer[$storeId];
    }

    /**
     * create new indexer Instance
     *
     * @param int $storeId
     * @return Indexer
     */
    public function createIndexer(int $storeId): Indexer
    {
        return new SmartDevs_ElastiCommerce_Indexer(
            $this->getConfig($storeId)
        );
    }
}