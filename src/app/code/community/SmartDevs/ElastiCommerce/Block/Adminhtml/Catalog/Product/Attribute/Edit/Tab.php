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
        $attribute = Mage::registry('entity_attribute');
        $fieldset = $form->addFieldset('elasticommerce', array('legend' => Mage::helper('elasticommerce')->__('Improve search results and user experience with following settings.')));
        // elasticsearch properties fieldset
        $fieldset->addField('is_used_for_boosted_search', 'select', array(
            'label' => 'Boosted Search',
            'class' => 'required-entry',
            'value' => $attribute->getData('is_used_for_boosted_search'),
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'name' => 'is_used_for_boosted_search',
            'values' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => sprintf('<p class="nm"><small>%s</small></p>',
                Mage::helper('elasticommerce')->__('Values of this field should be boosted in search results')
            )
        ));
        $fieldset->addField('is_used_for_completion', 'select', array(
            'label' => 'Autocompletion',
            'class' => 'required-entry',
            'value' => $attribute->getData('is_used_for_completion'),
            'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
            'name' => 'is_used_for_completion',
            'values' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'after_element_html' => sprintf('<p class="nm"><small>%s</small></p>',
                Mage::helper('elasticommerce')->__('Values of this field contains terms that might be suggested as an alternative spelling when a user makes a typo.')
            )
        ));
        if(Mage::helper('core')->isModuleEnabled('SmartDevs_ElastiCommerceFilter')){
            $fieldset->addField('filter_renderer', 'select', array(
                'label' => 'Filter Renderer',
                'class' => 'required-entry',
                'value' => $attribute->getData('filter_renderer'),
                'values' => Mage::getModel('elasticommercefilter/system_config_source_renderer')->toOptionArray(),
                'name' => 'filter_renderer',
                'after_element_html' => sprintf('<p class="nm"><small>%s</small></p>',
                    Mage::helper('elasticommerce')->__('Values of this field contains terms that might be suggested as an alternative spelling when a user makes a typo.')
                )
            ));

            $fieldset->addField('filter_renderer_format', 'text', array(
                'label' => 'Renderer Format',
                'value' => $attribute->getData('filter_renderer_format'),
                'name' => 'filter_renderer_format',
                'after_element_html' => sprintf('<p class="nm"><small>%s</small></p>',
                    Mage::helper('elasticommerce')->__('A format string used to format filter values. ')
                )
            ));

            $fieldset->addField('is_extended_filter', 'select', array(
                'label' => 'Extended Filter',
                'value' => $attribute->getData('is_extended_filter'),
                'values' => Mage::getModel('adminhtml/system_config_source_yesno')->toOptionArray(),
                'name' => 'is_extended_filter',
                'values' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
                'after_element_html' => sprintf('<p class="nm"><small>%s</small></p>',
                    Mage::helper('elasticommerce')->__('Extended Filters may be handeled differently, ex: hidden by default')
                )
            ));

        }
        $this->setForm($form);
        return $this;
    }
}
