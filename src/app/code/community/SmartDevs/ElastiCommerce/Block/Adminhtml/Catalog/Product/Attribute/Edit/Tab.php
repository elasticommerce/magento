<?php

/**
 * adds elasticommerce tab to adminhtml product attribute edit page
 *
 */
class SmartDevs_ElastiCommerce_Block_Adminhtml_Catalog_Product_Attribute_Edit_Tab extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Return Tab label
     *
     * @return string
     */
    public function getTabLabel()
    {
        return Mage::helper('elasticommerce')->__('ElastiCommerce');
    }

    /**
     * Return Tab title
     *
     * @return string
     */
    public function getTabTitle()
    {
        return Mage::helper('elasticommerce')->__('ElastiCommerce Attribute settings');
    }

    /**
     * Can show tab in tabs
     *
     * @return boolean
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Tab is hidden
     *
     * @return boolean
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return SmartDevs_ElastiCommerce_Block_Adminhtml_Catalog_Product_Attribute_Edit_Tab
     */
    protected function _prepareForm()
    {
        /* @var $form Varien_Data_Form */
        $form = new Varien_Data_Form();
        $fieldset = $form->addFieldset('elasticommerce', array('legend' => Mage::helper('elasticommerce')->__('Improve search results and user experience with following settings.')));
        // elasticsearch properties fieldset
        $fieldset->addField('is_used_for_boosted_search', 'select', array(
            'label' => 'Boosted Search',
            'class' => 'required-entry',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'name' => 'is_used_for_boosted_search',
            'after_element_html' => sprintf('<p class="nm"><small>%s</small></p>',
                Mage::helper('elasticommerce')->__('Values of this field should be boosted in search results')
            )
        ));
        $fieldset->addField('is_used_for_completion', 'select', array(
            'label' => 'Autocompletion',
            'class' => 'required-entry',
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'name' => 'is_used_for_completion',
            'after_element_html' => sprintf('<p class="nm"><small>%s</small></p>',
                Mage::helper('elasticommerce')->__('Values of this field contains terms that might be suggested as an alternative spelling when a user makes a typo.')
            )
        ));
        $this->setForm($form);
        return $this;
    }
}