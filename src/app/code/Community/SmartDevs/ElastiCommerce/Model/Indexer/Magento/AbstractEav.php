<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:25
 */
abstract class SmartDevs_ElastiCommerce_Model_Indexer_Magento_AbstractEav
    extends SmartDevs_ElastiCommerce_Model_Indexer_Magento_Abstract
{


    /**
     * entity type model
     *
     * @var Mage_Eav_Model_Entity_Type
     */
    protected $entity = null;

    /**
     * @var Mage_Eav_Model_Entity_Attribute[]
     */
    protected $entityAttributes = null;

    /**
     * @var Mage_Eav_Model_Entity_Attribute[]
     */
    protected $entityAttributeMap = null;

    /**
     * get current entity type code
     *
     * @return string
     */
    abstract protected function getEntityTypeCode();

    /**
     * get entity type
     *
     * @return Mage_Eav_Model_Entity_Type
     */
    protected function getEntity()
    {
        if (null === $this->entity) {
            $this->entity = Mage::getSingleton('eav/config')->getEntityType($this->getEntityTypeCode());
        }
        return $this->entity;
    }

    /**
     * get all entity attributes
     *
     * @return Mage_Eav_Model_Entity_Attribute[]
     */
    public function getEntityAttributes()
    {
        if (null === $this->entityAttributes) {
            //preload attribute codes
            $attributeCodes = Mage::getSingleton('eav/config')->getEntityAttributeCodes($this->getEntity());
            foreach ($attributeCodes as $attributeCode) {
                /** @var Mage_Eav_Model_Entity_Attribute $attribute */
                $attribute = Mage::getSingleton('eav/config')->getAttribute($this->getEntity(), $attributeCode);
                // check if exists source and backend model.
                // To prevent exception when some module was disabled
                $attribute->usesSource() && $attribute->getSource();
                $attribute->getBackend();
                $this->entityAttributes[$attribute->getId()] = $attribute;
                $this->entityAttributeMap[$attributeCode] = $attribute->getId();
            }
        }
        return $this->entityAttributes;
    }
}