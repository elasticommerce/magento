<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:25
 */
abstract class SmartDevs_ElastiCommerce_Model_Indexer_Type_AbstractEav
    extends SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
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
     * @var int[]
     */
    protected $entityAttributeMap = [];

    /**
     * @var string[]|int[]
     */
    protected $entityAttributeSortColumnMap = [];

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
                if (true === boolval($attribute->getUsedForSortBy())) {
                    $attribute->setSortColumnField($this->getAttributeSortColumn($attribute));
                    $attribute->setSortFieldType($this->getAttributeSortFieldType($attribute));
                }
            }
        }
        return $this->entityAttributes;
    }

    protected function getAttributeSortFieldType(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        $columns = $attribute->getFlatColumns();
        if (!isset($columns[$attribute->getSortColumnField()])) {
            return 'sort-string';
        }
        $sqltype = $columns[$attribute->getSortColumnField()]['type'];
        switch (true) {
            case strpos($sqltype, 'smallint') === 0:
            case strpos($sqltype, 'tinyint') === 0:
            case strpos($sqltype, 'int') === 0:
            case strpos($sqltype, 'decimal') === 0: {
                return 'sort-numeric';
            }
            case strpos($sqltype, 'datetime') === 0:
            case strpos($sqltype, 'timestamp') === 0: {
                return 'sort-date';
            }
            default: {
                return 'sort-string';
            }
        }
    }

    /**
     * Retrieve Attribute Sort column name
     *
     * @param string $attributeCode
     * @return string
     */
    protected function getAttributeSortColumn(Mage_Eav_Model_Entity_Attribute $attribute)
    {
        $columns = $attribute->getFlatColumns();
        if (false === isset($columns[$attribute->getAttributeCode()])) {
            return false;
        }
        $attributeIndex = sprintf('%s_value', $attribute->getAttributeCode());
        if (true === isset($columns[$attributeIndex])) {
            return $attributeIndex;
        }
        return $attribute->getAttributeCode();
    }
}