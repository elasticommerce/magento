<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('catalog/eav_attribute'),
    'filter_group',
    "varchar(255)"
);

$installer->getConnection()->addColumn(
    $installer->getTable('catalog/eav_attribute'),
    'filter_sort',
    "int(3) unsigned NOT NULL DEFAULT '0' after `filter_group`"
);
