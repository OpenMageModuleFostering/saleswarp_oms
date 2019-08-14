<?php
/**
 * CUSTOM API EXTENSION - 6th Street Inc
 * @author David Potts
 * @copyright 6th Street Inc.
 * @license none 
 * 
 */
class Saleswarp_Publish_Model_Category_Api extends Saleswarp_Oms_Model_Category_Api
{
	public $category_required_storeIds = array();
	
	/**
	 * Update saleswarp Category Id in Magento
	 *
	 * @params Magento Product Id, Saleswarp categroy Id
	 * @return bool( TRUE|FALSE)
	 */
	public function update_slwp_category_id($entityId, $slwpCatId)
	{
		return $this->rest_save_attribute($entityId, 'saleswarp_cat_id', $slwpCatId);
	}
	
	/**
	 * Update catalog/category attribute value 
	 *
	 * @params Magento Product id, attibure name, value
	 * @return query response
	 */
	function rest_save_attribute($entity_id, $code, $value, $store_id = 0)
	{
		$resource	= Mage::getSingleton('core/resource');
		
		$entity_type_id = Mage::getModel('eav/entity')->setType('catalog_category')->getTypeId();
		
		$query = "SELECT attribute_id, backend_type, frontend_input FROM `" . $resource->getTableName('eav_attribute') . "` 
					WHERE `attribute_code` LIKE :code AND entity_type_id = :entityTypeId";
		
		$binds = array(
			'code' => $code,
			'entityTypeId' => $entity_type_id
		);
		
		$result = $resource->getConnection('core_read')->query($query, $binds);
		$row    = $result->fetch(PDO::FETCH_ASSOC);
		return $this->save_attribute($row, $entity_id, $entity_type_id, $code, $value, $store_id);
	}
	
	
	/**
	 * special attribute saves for select and multiselect types (frontend_type in Magento)
	 *
	 * @param mixed $product_id
	 * @param mixed $attribute_id
	 * @param mixed $attribute_code
	 */
	function save_attribute($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id)
	{
		$resource	= Mage::getSingleton('core/resource');
		$write 		= $resource->getConnection('core_write');
		$read  		= $resource->getConnection('core_read');
		
		
		if ((!empty($row)) && ($row['backend_type'] != '') && ($row['backend_type'] != null)) {
			
			$categroyBakTable = $resource->getTableName('catalog_category_entity_' . $row['backend_type']);

			// does this exist
			$query  = "SELECT * FROM  `" . $categroyBakTable . "`
						WHERE attribute_id = :attributeId 
						AND entity_id = :entityId
						AND store_id = 0
						AND entity_type_id = :entityTypeId";
			$binds  = array(
				'attributeId' => $row['attribute_id'],
				'entityId' => $entity_id,
				'entityTypeId' => $entity_type_id
			);

			$result = $read->query($query, $binds);
			$chk    = $result->fetch(PDO::FETCH_ASSOC);
			
			if (empty($chk)) {
				// If key is in array $this->product_required_storeIds, then create an Admin store_id entry (store_id = 0)
				if (!empty($value) && $value != "") {
					$key1 = "catalog_category_entity_" . $row['backend_type'];
					if (array_key_exists($key1, $this->category_required_storeIds)) {
						
						$sql   = "INSERT INTO `" . $categroyBakTable . "` (value_id,  entity_type_id, attribute_id, store_id, entity_id, value)
                                VALUES (NULL ,:etype,:attr,0,:eid,:val) ";
						$binds = array(
							'etype' => $entity_type_id,
							'attr' 	=> $row['attribute_id'],
							'eid' 	=> $entity_id,
							'val' 	=> $value
						);
						
						$write->query($sql, $binds);
					}
				}
			} else { // update 
				$sql = "UPDATE `" . $categroyBakTable . "` 
                        SET value = :value
                        WHERE value_id = :value_id";
				
				$binds  = array(
					'value' 	=> $value,
					'value_id' 	=> $chk['value_id']
				);
				$result = $write->query($sql, $binds);
			}
			
			// check for store id = 1
			$query = "SELECT * FROM  `" . $categroyBakTable . "`
						WHERE attribute_id = :attributeId 
						AND entity_id = :entityId
						AND store_id = :storeId
						AND entity_type_id = :entityTypeId";
			$binds = array(
				'attributeId' 	=> $row['attribute_id'],
				'entityId' 		=> $entity_id,
				'storeId' 		=> $store_id,
				'entityTypeId' 	=> $entity_type_id
			);
			$result = $read->query($query, $binds);
			$chk    = $result->fetch(PDO::FETCH_ASSOC);
			
			if (empty($chk)) {
				if (!empty($value) && $value != "") {
					$sql   = "INSERT INTO `" . $categroyBakTable . "` (value_id, entity_type_id, attribute_id, store_id, entity_id, value)
                            VALUES (NULL , :entity_type_id, :attribute_id, :store_id, :entity_id,:value)";

					$binds = array(
						'entity_type_id'=> $entity_type_id,
						'attribute_id' 	=> $row['attribute_id'],
						'store_id' 		=> $store_id,
						'entity_id' 	=> $entity_id,
						'value' 		=> $value
					);

					$write->query($sql, $binds);
				}
			} else { // update 
				if (!empty($value) && $value != "") {
					$sql = "UPDATE `" . $categroyBakTable . "` 
							SET value = :value
							WHERE value_id = :value_id";
					
					$binds  = array(
						'value' 	=> $value,
						'value_id' 	=> $chk['value_id']
					);
					$result = $write->query($sql, $binds);
				}
			}
		}
		return "pass";
	}
	
	
	/**
	 * Get category collection by default root category 
	 *
	 * @params Magento root category Id
	 * @return Mage Category Collection
	 */
	public function get_category_collection($mageRootId)
	{
		Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		$collection = Mage::getModel('catalog/category')
						->getCollection()
						->addAttributeToSelect('entity_id')
						->addAttributeToSelect('name')
						->addAttributeToSelect('saleswarp_cat_id')
						->addFieldToFilter('parent_id', $mageRootId);

		if ($collection) {
			$data = array();
			foreach ($collection as $category) {
				$data[] = $category->getData();
			}
			return $data;
		}
		return false;
	}
	
	
	/**
	 * Check if category exists in magento ( serach by name )
	 * 
	 * @params id and saleswarp value
	 * @return bool
	 */
	public function check_category_exists($attr_id, $warehouse_cat_code)
	{
		$resource			= Mage::getSingleton('core/resource');
		$entity_type_id 	= Mage::getModel('eav/entity')->setType('catalog_category')->getTypeId();
		
		$sql   = "SELECT * FROM " . $resource->getTableName('catalog_category_entity_int') . "
					WHERE attribute_id = :attributeId
					and   value = :value
					and   entity_type_id = :entityTypeId";
		$binds = array(
			'attributeId' 	=> $attr_id,
			'value'			=> $warehouse_cat_code,
			'entityTypeId' 	=> $entity_type_id
		);
		$cat   = $resource->getConnection('core_read')->fetchAll($sql, $binds);
		
		if (!empty($cat)) {
			return $cat[0]['entity_id'];
		} else {
			return false;
		}
	}
	
	/**
	 * Get category names
	 * 
	 * @param mixed $store_cat_id
	 * @return mixed
	 */
	public function get_category_name($cat_ids)
	{
		$resource			= Mage::getSingleton('core/resource');
		$category         	= '';
		
		foreach ($cat_ids as $cat_id) {
			$sql = "SELECT value from " . $resource->getTableName('catalog_category_entity_varchar') . " as a
					JOIN  " . $resource->getTableName('eav_attribute') . " as b ON a.attribute_id = b.attribute_id
					WHERE a.entity_id = :catId AND b.attribute_code = 'name'";
			
			$binds    = array(
				'catId' => $cat_id
			);
			$cats     = $resource->getConnection('core_read')->fetchAll($sql, $binds);
			$category = $cats[0]['value'] . ',' . $category;
		}
		
		$category = substr_replace($category, "", -1);
		return $category;
	}
	
	/**
	 * Check_Category_Exists_Id: 
	 * 
	 * @param mixed $store_cat_id
	 * @return mixed
	 */
	public function check_parent_cat($store_cat_id, $root_cat_id)
	{
		$resource	= Mage::getSingleton('core/resource');
		$sql		= "SELECT path FROM  " . $resource->getTableName('catalog_category_entity') . "
						WHERE entity_id = :catId ";
		$binds		= array(
			'catId' => $store_cat_id
		);
		$cats          = $resource->getConnection('core_read')->fetchRow($sql, $binds);
		
		$parent_cat = array();
		if ($cats) {
			$catIds     = substr($cats['path'], 4);
			$parent_cat = explode('/', $catIds);
			return $parent_cat;
		} else {
			return false;
		}
	}
	
	/**
	 * Get all categories by parent category
	 *
	 * @params category id and root category id
	 * @return category (array)
	 */
	public function check_parent_cat_new($store_cat_id, $root_cat_id)
	{
		$resource	= Mage::getSingleton('core/resource');

		$sql   		= "SELECT path FROM  " . $resource->getConnection('core_read') . "
						WHERE entity_id = :catId ";
		$binds 		= array(
			'catId' => $store_cat_id
		);
		$cats  = $resource->getConnection('core_read')->fetchRow($sql, $binds);
		
		$parent_cat = array();
		if ($cats) {
			$parent_cat = explode('/', $cats['path']);
			$i          = 0;
			foreach ($parent_cat as $cats) {
				if ($i > 1) {
					$cat[] = $cats;
				}
				$i++;
			}
			return $cat;
		} else {
			return false;
		}
	}
	
	/**
	 * Check_Category_Exists_Id: 
	 * 
	 * @param mixed $store_cat_id
	 * @return mixed
	 */
	public function check_category_exists_id($store_cat_id)
	{
		$resource		= Mage::getSingleton('core/resource');
		
		$sql   = "SELECT * FROM  " . $resource->getTableName('catalog_category_entity') . " 
					WHERE entity_id = :catId ";
		$binds = array(
			'catId' => $store_cat_id
		);
		$cats  = $resource->getConnection('core_read')->fetchAll($sql, $binds);
		if (!empty($cat)) {
			return true;
		} else {
			return false;
		}
	}
}
?> 