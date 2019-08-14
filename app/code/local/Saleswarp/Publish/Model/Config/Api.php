<?php
/**
 * Use this API to get/set key config data for the storefronts
 */
class Saleswarp_Publish_Model_Config_Api extends Saleswarp_Oms_Model_Config_Api
{
	public $activeStoreId = null;
	
	/**
	 * get all attributes in form of saleswarp_xxx and xxx_saleswarp
	 */
	public function getSaleswarpAttributeIds()
	{
		$resource		= Mage::getSingleton('core/resource');
		$sql          	= "SELECT * from " . $resource->getTableName('eav_attribute') . " WHERE attribute_code = %saleswarp%";
		$atts         	= $resource->getConnection('core_read')->fetchAll($sql);
		return $atts;
	}

	/**
	 * get all product attributes
	 */
	public function getProductAttributesIds()
	{
		$eavAttrTable = Mage::getSingleton( 'core/resource' )->getTableName( 'eav_attribute' );
		$entity_type_id = Mage::getModel('eav/entity')
					->setType('catalog_product')
					->getTypeId();   
		$sql = "SELECT * from ". $eavAttrTable ."
				WHERE entity_type_id = '".(int)$entity_type_id."' ";
		$atts = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);  
		return $atts;
	} 

	/**
	 * Get all category attibute id 
	 */
	public function getCategoryAttributesIds()
	{
		$resource		= Mage::getSingleton('core/resource');
		$entity_type_id = Mage::getModel('eav/entity')->setType('catalog_category')->getTypeId();
		$sql            = "SELECT * from " . $resource->getTableName('eav_attribute') . " 
							WHERE entity_type_id = '" . (int) $entity_type_id . "'";
		$atts           = $resource->getConnection('core_read')->fetchAll($sql);
		return $atts;
	}
	
	/**
	 * with Magento EAV architecture we need to retrieve key Attributes Key ID's before publishing any
	 * product or category info 
	 * 
	 */
	public function getAttributeIds()
	{
		$resource		= Mage::getSingleton('core/resource');
		$sql      = "SELECT * from  " . $resource->getTableName('eav_entity_type') . " WHERE 1";
		$entities = $resource->getConnection('core_read')->fetchAll($sql);
		return $entities;
	}
	
	/**
	 * Function Not in use
	 */
	public function getStoreInfo($storeId = null)
	{
		if ($storeId == null) {
			if ($this->activeStoreId != null) {
				$storeId = $this->activeStoreId;
			} else {
				return false;
			}
		}
	}
	
	/**
	 * get Store configs
	 */
	public function getSetupConfigs()
	{
		return Mage::getSingleton('core/resource')->getConnection('core_read')->getConfig();
	}
}
?>