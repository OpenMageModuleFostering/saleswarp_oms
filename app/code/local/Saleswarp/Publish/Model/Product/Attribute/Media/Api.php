<?php
class Saleswarp_Publish_Model_Product_Attribute_Media_Api extends Saleswarp_Oms_Model_Product_Attribute_Media_Api
{
	/**
	 * QUICK Update image data
	 * CAUTION: removed check for existing image, useful for mass ports 
	 * 
	 * @author David Potts, 6th Street Inc. 
	 * 
	 * @param int|string $productId
	 * @param string $file
	 * @param array $data
	 * @param string|int $store
	 * @return boolean
	 */
	public function quick_update($attr_id, $productId, $file, $img_type, $position = 0, $store = 0)
	{
		$read  = Mage::getSingleton('core/resource')->getConnection('core_read');
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		
		$result = true;
		$sql   = "select value_id from catalog_product_entity_media_gallery  
					where attribute_id = :attrId 
					and value = :file 
					and entity_id = :productId ";
		$binds = array(
			'attrId' 	=> $attr_id,
			'file' 		=> $file,
			'productId' => $productId
		);
		$data  = $read->fetchAll($sql, $binds);
		
		if (count($data) == 0) {
			if ($img_type == 'image' || $img_type == 'alt_image') {
				// only include image & alt_image in media gallery   
				$disable = 0;
			} else {
				$disable = 1;
			}
			
			$sql = "insert into catalog_product_entity_media_gallery(value_id, attribute_id, entity_id, value) 
						values(value_id, :attrId, :productId, :file)";
			
			$write->query($sql, $binds);
			$value_id = $write->lastInsertId();
			
			$sql = "insert into catalog_product_entity_media_gallery_value(value_id, store_id, label, position, disabled) 
						values(:valueId, :store, :imgType, :position, :disable)";
			unset($binds);
			$binds = array(
				'valueId' 	=> $value_id,
				'store' 	=> $store,
				'imgType' 	=> $img_type,
				'position' 	=> $position,
				'disable' 	=> $disable
			);
			$write->query($sql, $binds);
			
			// put in thumbnail & base references 
			switch ($img_type) {
				case "image":
					$upd1 = $this->update_entity_varchar("4", "85", $store, $productId, $file);
					// check store 0 as 
					$upd2 = $this->update_entity_varchar("4", "85", "0", $productId, $file);
					
					if ($upd1 == false || $upd2 == false) {
						$result = false;
					}
					break;
				case "thumbnail":
					$upd1 = $this->update_entity_varchar("4", "87", $store, $productId, $file);
					$upd2 = $this->update_entity_varchar("4", "87", "0", $productId, $file);
					if ($upd1 == false || $upd2 == false) {
						$result = false;
					}
					break;
				case "small_image":
					$upd1 = $this->update_entity_varchar("4", "86", $store, $productId, $file);
					$upd2 = $this->update_entity_varchar("4", "86", "0", $productId, $file);
					if ($upd1 == false || $upd2 == false) {
						$result = false;
					}
					break;
				default:
					$upd1 = $this->update_entity_varchar("4", "85", $store, $productId, $file);
					$upd2 = $this->update_entity_varchar("4", "85", "0", $productId, $file);
					if ($upd1 == false || $upd2 == false) {
						$result = false;
					}
					break;
			}
		}
		return $result;
	}
	
	protected function update_entity_varchar($entity_type_id, $attribute_id, $store_id, $entity_id, $value)
	{
		$result = true;
		$read   = Mage::getSingleton('core/resource')->getConnection('core_read');
		$write  = Mage::getSingleton('core/resource')->getConnection('core_write');
		$sql    = "select value_id from catalog_product_entity_varchar  
					where entity_type_id = :entityTypeId
					and  attribute_id = :attributeId 
					and  store_id = :storeId 
					and  entity_id = :entityId ";
		
		$binds = array(
			'entityTypeId' 	=> $entity_type_id,
			'attributeId' 	=> $attribute_id,
			'storeId' 		=> $store_id,
			'entityId' 		=> $entity_id
		);
		
		$data1 = $read->fetchRow($sql, $binds);
		if (empty($data1)) {
			$sql     = "insert into catalog_product_entity_varchar(value_id, entity_type_id, attribute_id, store_id, entity_id, value) 
                        values(value_id, :entityTypeId, :attributeId, :storeId, :entityId, :value) ";
			$binds   = array(
				'entityTypeId' 	=> $entity_type_id,
				'attributeId' 	=> $attribute_id,
				'storeId' 		=> $store_id,
				'entityId' 		=> $entity_id,
				'value' 		=> $value
			);
			$qresult = $write->query($sql, $binds);
			if ($qresult == false) {
				$result = false;
			}
		} else {
			// do an update only  
			$value_id = $data1['value_id'];
			$sql      = "UPDATE catalog_product_entity_varchar 
							SET 
								entity_type_id = :entityTypeId,
								attribute_id   = :attributeId,
								store_id       = :storeId, 
								entity_id      = :entityId, 
								value          = :value 
							WHERE value_id = :valueId LIMIT 1";
			$binds    = array(
				'entityTypeId' 	=> $entity_type_id,
				'attributeId' 	=> $attribute_id,
				'storeId' 		=> $store_id,
				'entityId' 		=> $entity_id,
				'value' 		=> $value,
				'valueId' 		=> $value_id
			);
			$qresult  = $write->query($sql, $binds);
		}
		return $result;
	}
}
?>