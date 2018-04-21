<?php

$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $installer->getTable('catalog/eav_attribute'),
    'is_extended_filter',
    "tinyint(1) unsigned NOT NULL DEFAULT '0' after `filter_renderer_format`"
);

