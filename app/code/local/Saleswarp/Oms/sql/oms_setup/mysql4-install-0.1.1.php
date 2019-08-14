<?php
/**
 * This module connects Magento to the SalesWarp Advanced Order Management System.
 * 
 * @copyright      Copyright (c) 2015 6th Street Inc, dba SalesWarp.
 * @version        0.1.2
 * @author         David Potts
 * @license        http://www.saleswarp.com/license-saas
 * @category       SalesWarp
 * @since          File available since Release 0.1.2
 * @link           https://www.SalesWarp.com
 *
*/

$installer = $this;
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

$constants	= array(
	'saleswarp_api_url'	=> 'http://accounts.saleswarp.com/key/get',
	'saleswarp_create_url'	=> 'https://www.saleswarp.com/30-day-free-trial-sign-up/',
	'saleswarp_login_url'	=> 'https://www.saleswarp.com/SMB_login',
);

foreach($constants as $code	=> $value) {
	Mage::getModel('core/variable')
		->setCode($code)
		->setName($code)
		->setPlainValue($value)
		->save();
}

$setup->addAttribute('catalog_product', 'saleswarp_prod_id', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'int',
    'input'                    => 'text',
    'label'                    => 'SalesWarp ID',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => true,
    'user_defined'             => true,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$setup->addAttribute('catalog_product', 'saleswarp_prod_add_date', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'datetime',
    'input'                    => 'date',
    'label'                    => 'SalesWarp Add Date',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'backend'                   => 'eav/entity_attribute_backend_datetime',
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => true,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$setup->addAttribute('catalog_product', 'saleswarp_prod_code', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'varchar',
    'input'                    => 'text',
    'label'                    => 'SalesWarp Code',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => true,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$setup->addAttribute('catalog_product', 'saleswarp_prod_sync_date', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'datetime',
    'input'                    => 'date',
    'label'                    => 'SalesWarp Sync Date',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'backend'                   => 'eav/entity_attribute_backend_datetime',
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => false,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$setup->addAttribute('catalog_product', 'saleswarp_prod_last_inv_upd', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'datetime',
    'input'                    => 'date',
    'label'                    => 'SalesWarp Last Inventory Update',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'backend'                   => 'eav/entity_attribute_backend_datetime',
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => false,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$setup->addAttribute('catalog_category', 'saleswarp_cat_id', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'int',
    'input'                    => 'text',
    'label'                    => 'SalesWarp ID',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => false,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$setup->addAttribute('catalog_category', 'saleswarp_cat_name', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'varchar',
    'input'                    => 'text',
    'label'                    => 'SalesWarp Name',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => false,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$setup->addAttribute('catalog_category', 'saleswarp_cat_code', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'int',
    'input'                    => 'text',
    'label'                    => 'SalesWarp Code',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => false,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$setup->addAttribute('catalog_category', 'saleswarp_cat_add_date', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'datetime',
    'input'                    => 'date',
    'label'                    => 'SalesWarp Add Date',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'backend'                   => 'eav/entity_attribute_backend_datetime',
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => false,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$setup->addAttribute('catalog_category', 'saleswarp_cat_sync_date', array(
    'group'                    => 'SalesWarp',
    'type'                     => 'datetime',
    'input'                    => 'date',
    'label'                    => 'SalesWarp Sync Date',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
    'required'                 => 0,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'backend'                   => 'eav/entity_attribute_backend_datetime',
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => false,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));


$productTable = $this->getTable('catalog_product_entity');
// Add new text fields product tables
$installer->getConnection()->addColumn($productTable, 'saleswarp_category_ids', 'varchar(64) NULL');

// ADD SALESWARP ATTRIBUTES FOR PRODUCTS 
$setup->addAttribute('catalog_product', 'managed_by_saleswarp', array(
    'group'                    => 'General',
    'type'                     => 'int',
    'input'                    => 'boolean',
    'label'                    => 'Manage Product with SalesWarp',
    'global'                   => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'                  => 1,
	'default'           	   => '0',
    'required'                 => 1,
    'visible_on_front'         => 0,
    'is_html_allowed_on_front' => 0,
    'is_configurable'          => 0,
    'searchable'               => 0,
    'filterable'               => 0,
    'comparable'               => 0,
    'unique'                   => false,
    'user_defined'             => false,
    'is_user_defined'          => false,
    'used_in_product_listing'  => false
));

$installer->endSetup();
