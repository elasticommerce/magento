<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:25
 */
abstract class SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract_Eav
    extends SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
{

    /**
     * get entity type
     *
     * @return Mage_Eav_Model_Entity_Type
     */
    protected function getEntity()
    {
        if (null === $this->_entity) {
            $this->_entity = Mage::getSingleton('eav/config')->getEntityType($this->getEntityTypeCode());
        }
        return $this->_entity;
    }

    /**
     * get all entity attributes
     *
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    public function getEntityAttributes()
    {
        if (null === $this->_entityAttributes) {
            //preload attribute codes
            $attributeCodes = Mage::getSingleton('eav/config')->getEntityAttributeCodes($this->getEntity());
            foreach ($attributeCodes as $attributeCode) {
                /** @var Mage_Eav_Model_Entity_Attribute $attribute */
                $attribute = Mage::getSingleton('eav/config')->getAttribute($this->getEntity(), $attributeCode);
                // check if exists source and backend model.
                // To prevent exception when some module was disabled
                $attribute->usesSource() && $attribute->getSource();
                $attribute->getBackend();
                $this->_entityAttributes[$attribute->getId()] = $attribute;
                $this->_entityAttributeLookup[$attributeCode] = $attribute->getId();
            }
        }
        return $this->_entityAttributes;
    }
}