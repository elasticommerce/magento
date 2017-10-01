<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 19.02.17
 * Time: 11:05
 */
class SmartDevs_ElastiCommerce_IndexDocument extends SmartDevs\ElastiCommerce\Index\Document
{
    public function addResultData(array $data){
        $this->_data['result'] += $data;
    }

    /**
     * add sort by string value
     *
     * @param $key
     * @param $value
     */
    public function addSortData($type = 'sort-string', $key, $value)
    {
        $this->_data[$type][$key] = $value;
    }
}