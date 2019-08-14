<?php
class Saleswarp_Publish_Model_Product_Attribute_Api extends Saleswarp_Oms_Model_Product_Attribute_Api
{
	/**
	 * Add manufacturer
	 **/
	function add_manf($attributeId = 102, $saleswarp_manf_name, $store_id = 0)
	{
		$read  = Mage::getSingleton('core/resource')->getConnection('core_read');
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		
		$sql = "select option_id from eav_attribute_option_value 
                where value = :saleswarp_manf_name AND store_id = :store_id ";
		
		$binds = array(
			'saleswarp_manf_name' 	=> $saleswarp_manf_name,
			'store_id' 				=> $store_id
		);
		$data  = $read->fetchAll($sql, $binds);
		
		if (count($data) == 0) {
			$write->query("insert into eav_attribute_option (attribute_id) values (" . (int) $attributeId . ")");
			$option_id = $connection->lastInsertId();
			$sql       = "insert into eav_attribute_option_value(option_id,value,store_id) 
                                values(:option_id, :saleswarp_manf_name, :store_id)";

			$binds     = array(
				'option_id' 			=> $option_id,
				'saleswarp_manf_name' 	=> $saleswarp_manf_name,
				'store_id' 				=> $store_id
			);
			$write->query($sql, $binds);
		} else {
			$option_id = $data[0]['option_id'];
		}
		return $option_id;
	}	
}