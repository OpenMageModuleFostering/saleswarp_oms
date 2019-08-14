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
/**
 * Use this API to get/set key config data for the storefronts
 */
class Saleswarp_Oms_Model_Config_Api
{
	/**
	* get Attribute id by attribute name
	* @params attribute name
	* @return attribute id
	*/
	public function getProductAttributesId($attribute_code)
	{
		$resource		= Mage::getSingleton('core/resource');
		$entity_type_id = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
		
		$sql = "SELECT attribute_id from " . $resource->getTableName('eav_attribute') . "
				WHERE entity_type_id = :entityTypeId
				AND attribute_code = :attributeCode ";
				
		$binds = array(
			'entityTypeId' => $entity_type_id,
			'attributeCode' => $attribute_code
		);
		$att   = $resource->getConnection('core_read')->fetchAll($sql, $binds);
		return $att;
	}

	/**
	* get website and store Id 
	*/
	public function getStoreIds()
	{
		$resource	= Mage::getSingleton('core/resource');
		$sql    = "SELECT str.*, web.* from " . $resource->getTableName('core_store') . " as str 
					JOIN " . $resource->getTableName('core_website') . " as web ON str.website_id = web.website_id ";
					$stores = $resource->getConnection('core_read')->fetchAll($sql);
		return $stores;
	}
}
?>
