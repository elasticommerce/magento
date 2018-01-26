<?php
use SmartDevs\ElastiCommerce\Index\Settings as AbstractSettings;

class SmartDevs_ElastiCommerce_Model_Index_Settings extends AbstractSettings
{
    /**
     * cache key for storage
     */
    const CACHE_KEY = 'elasticommerce_config_settings_%s';

    /**
     * cache tag for storage
     */
    const CACHE_TAG = 'ELASTICOMMERCE';

    /**
     * @var Mage_Core_Model_Store
     */
    protected $store = null;

    /**
     * SmartDevs_ElastiCommerce_Model_ElastiCommerce_Index_Settings constructor.
     *
     * @param array $params
     * @throws Exception
     */
    public function __construct(array $params)
    {
        if (false === isset($params['store'])) {
            throw new Exception('missing required parameter "store".');
        }
        $this->setStore($params['store']);
        $this->init();
    }

    /**
     * init required parameters
     * @todo init from cache
     */
    protected function init()
    {
        $this->initLocaleCode();
        $this->initLanguageCode();
        $this->initLanguage();
        $this->initAnalyzer();
    }

    /**
     * init current locale code
     *
     * @return SmartDevs_ElastiCommerce_Model_Index_Settings
     */
    protected function initLocaleCode()
    {
        $localeCode = (string)Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $this->getStore()->getId());
        if (true === empty($localeCode)) {
            $localeCode = false;
        }
        $this->setLocaleCode($localeCode);
        return $this;
    }

    /**
     * init current language code
     *
     * @return SmartDevs_ElastiCommerce_Model_Index_Settings
     */
    public function initLanguageCode()
    {
        //check we have an valid locale code
        if (false === $this->getLocaleCode()) {
            return $this->setLanguageCode(false);
        }
        //first set to unknown
        $languageCode = false;
        foreach ($this->supportedLanguageCodes as $code => $locales) {
            if (true === is_array($locales)) {
                if (true === in_array($this->getLocaleCode(), $locales)) {
                    $languageCode = $code;
                }
            } elseif ($this->getLocaleCode() == $locales) {
                $languageCode = $code;
            }
        }
        $this->setLanguageCode($languageCode);
        return $this;
    }

    /**
     * init current language name
     *
     * @return SmartDevs_ElastiCommerce_Model_Index_Settings
     */
    protected function initLanguage()
    {
        $this->setLanguage(Zend_Locale_Data::getContent('en_GB', 'language', $this->getLanguageCode()));
        return $this;
    }

    /**
     * @param Mage_Core_Model_Store $store
     * @return $this
     */
    protected function setStore(Mage_Core_Model_Store $store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * @return Mage_Core_Model_Store
     */
    protected function getStore()
    {
        return $this->store;
    }

    /**
     * @return array
     */
    protected function getAnalyzerConfigFiles()
    {
        $config = [];
        $config[] = sprintf('analysis_%s_%s.xml', $this->getStore()->getWebsite()->getCode(), $this->getStore()->getCode());
        $config[] = sprintf('analysis_%s.xml', $this->getLanguage());
        $config[] = sprintf('analysis.xml');
        return $config;
    }

}