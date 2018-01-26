<?php
declare(strict_types = 1);

use SmartDevs\ElastiCommerce\Config\ServerConfig;


/**
 * Class SmartDevs_ElastiCommerce_Model_Config_ServerConfig
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
final class SmartDevs_ElastiCommerce_Model_Config_ServerConfig extends ServerConfig
{
    /**
     * xml path to host configuration
     */
    const XML_CONFIG_PATH_HOST = 'elasticommerce/connection/host';

    /**
     * xml path to port configuration
     */
    const XML_CONFIG_PATH_PORT = 'elasticommerce/connection/port';

    /**
     * xml path to basic auth username
     */
    const XML_CONFIG_PATH_AUTH_USER = 'elasticommerce/connection/auth_username';

    /**
     * xml path to basic auth password
     */
    const XML_CONFIG_PATH_AUTH_PASS = 'elasticommerce/connection/auth_password';

    /**
     * SmartDevs_ElastiCommerce_Model_Config_ServerConfig constructor.
     * @param int $storeId
     */
    public function __construct(int $storeId)
    {
        $this->setHost(strval(Mage::getStoreConfig(self::XML_CONFIG_PATH_HOST, $storeId)));
        $this->setPort(intval(Mage::getStoreConfig(self::XML_CONFIG_PATH_PORT, $storeId)));
        if (false === empty(strval(Mage::getStoreConfig(self::XML_CONFIG_PATH_AUTH_USER, $storeId)))) {
            $this->setAuthUsername(strval(Mage::getStoreConfig(self::XML_CONFIG_PATH_AUTH_USER, $storeId)));
        }
        if (false === empty(strval(Mage::getStoreConfig(self::XML_CONFIG_PATH_AUTH_PASS, $storeId)))) {
            $this->setAuthPassword(strval(Mage::getStoreConfig(self::XML_CONFIG_PATH_AUTH_PASS, $storeId)));
        }
    }
}