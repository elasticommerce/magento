<?php
use SmartDevs\ElastiCommerce\Implementor\Config as ConfigInterface;
use SmartDevs\ElastiCommerce\Config\{
    ServerConfig, IndexConfig
};

final class SmartDevs_ElastiCommerce_Model_Config_Store implements ConfigInterface
{
    /**
     * @var ServerConfig
     */
    protected $serverConfig = null;

    /**
     * @var IndexConfig
     */
    protected $indexConfig = null;

    /**
     * @return ServerConfig
     */
    public function getServerConfig(): ServerConfig
    {
        if (null === $this->serverConfig) {
            throw new \UnexpectedValueException('Config::$serverConfig is not initialized.');
        }
        return $this->serverConfig;
    }

    /**
     * @return IndexConfig
     */
    public function getIndexConfig(): IndexConfig
    {
        if (null === $this->indexConfig) {
            throw new \UnexpectedValueException('Config::$indexConfig is not initialized.');
        }
        return $this->indexConfig;
    }

    /**
     * @param ServerConfig $serverConfig
     *
     * @return ConfigInterface
     */
    public function setServerConfig(ServerConfig $serverConfig): ConfigInterface
    {
        $this->serverConfig = $serverConfig;
        return $this;
    }

    /**
     * @param IndexConfig $indexConfig
     *
     * @return ConfigInterface
     */
    public function setIndexConfig(IndexConfig $indexConfig): ConfigInterface
    {
        $this->indexConfig = $indexConfig;
        return $this;
    }
}