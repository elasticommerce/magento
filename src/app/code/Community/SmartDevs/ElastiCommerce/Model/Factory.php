<?php
use SmartDevs\ElastiCommerce\Manager;
use SmartDevs\ElastiCommerce\Config\ServerConfig;
use SmartDevs\ElastiCommerce\Config\IndexConfig;
use SmartDevs\ElastiCommerce\Config\Config;

/**
 * Class SmartDevs_ElastiCommerce_Model_Manager
 */
class SmartDevs_ElastiCommerce_Model_Factory
{
    /**
     * @var Manager[]
     */
    protected $instances = array();

    /**
     * get ElastiCommerce manager instance
     *
     * @param int $storeId
     * @return Manager
     */
    public function getAdapter($storeId = null)
    {
        if (null === $storeId || true === empty($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }
        if (false === in_array((int)$storeId, $this->instances)) {
            $this->instances[(int)$storeId] = new Manager($this->getConfig($storeId));
        }
        return $this->instances[(int)$storeId];
    }

    /**
     * @return Config
     */
    protected function getConfig($storeId)
    {
        $config = new Config();
        $config->setServerConfig($this->getServerConfig($storeId));
        $config->setIndexConfig($this->getIndexConfig());
        return $config;
    }

    /**
     * @return ServerConfig
     */
    protected function getServerConfig($storeId)
    {
        $serverconfig = new ServerConfig();
        return $serverconfig;
    }

    /**
     * @param $storeId
     * @return IndexConfig
     */
    protected function getIndexConfig($storeId)
    {
        $indexConfig = new IndexConfig();
        return $indexConfig;
    }
}