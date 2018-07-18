<?php

class SmartDevs_ElastiCommerce_Model_Observer
{
    /**
     * Add missing delete event for categories
     *
     * @param Varien_Event_Observer $observer
     * @throws Exception
     */
    public function onCategoryDeleteCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Category $category */
        $category = $observer->getEvent()->getCategory();
        Mage::getSingleton('index/indexer')
            ->processEntityAction($category, Mage_Catalog_Model_Category::ENTITY, Mage_Index_Model_Event::TYPE_DELETE);
    }

    /**
     * Add missing save event for CMS pages
     *
     * @param Varien_Event_Observer $observer
     * @throws Exception
     */
    public function onCmsPageSaveCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Cms_Model_Page $page */
        $page = $observer->getEvent()->getObject();
        Mage::getSingleton('index/indexer')
            ->processEntityAction($page, 'cms_page', Mage_Index_Model_Event::TYPE_SAVE);
    }

    /**
     * Add missing delete event for CMS pages
     *
     * @param Varien_Event_Observer $observer
     * @throws Exception
     */
    public function onCmsPageDeleteCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Cms_Model_Page $page */
        $page = $observer->getEvent()->getObject();
        Mage::getSingleton('index/indexer')
            ->processEntityAction($page, 'cms_page', Mage_Index_Model_Event::TYPE_DELETE);
    }

    /**
     *
     */
    public function catalogProductSaveCommitAfter(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        Mage::getSingleton('index/indexer')
            ->processEntityAction($product, Mage_Catalog_Model_Product::ENTITY, Mage_Index_Model_Event::TYPE_SAVE);
    }
}