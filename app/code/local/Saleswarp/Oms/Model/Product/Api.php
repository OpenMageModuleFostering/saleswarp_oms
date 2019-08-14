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
class Saleswarp_Oms_Model_Product_Api extends Mage_Catalog_Model_Product_Api
{
	/**
	 * function to update configurable swatches
	 */
	 function enableConfigurableSwatches()
	 {
	 	 $eavAttrTable		= Mage::getSingleton('core/resource')->getTableName('eav_attribute');
	 	 $catalogAttrTable	= Mage::getSingleton('core/resource')->getTableName('catalog_eav_attribute');
	 	 $read				= Mage::getSingleton('core/resource')->getConnection('core_read');
		
		$sql				= "SELECT `e`.`attribute_id` FROM `" . $eavAttrTable . "` as `e` 
								JOIN `" . $catalogAttrTable . "` as `c` ON `e`.`attribute_id` = `c`.`attribute_id`
								WHERE `e`.`entity_type_id` = '4' 
								AND `e`.`frontend_input` = 'select' 
								AND `e`.`is_user_defined` = '1' 
								AND `c`.`is_configurable` = 1 ";
		$result				= $read->fetchAll($sql);
		if ($result) {
			$arr = array();
			foreach ($result as $id) {
				array_push($arr, $id['attribute_id']);
			}
			$ids = implode(',', $arr);
			if ($ids) {
				$configModel = new Mage_Core_Model_Config();
				$configModel->saveConfig('configswatches/general/enabled', "1", 'default', 0);
				$configModel->saveConfig('configswatches/general/swatch_attributes', $ids, 'default', 0);
			}
		}
		return $ids;
		}
		
	/**
	 * function to disble configurable swatches
	 */
	 function disableConfigurableSwatches()
	 {
	 	 $configModel = new Mage_Core_Model_Config();
	 	 $configModel->saveConfig('configswatches/general/enabled', "0", 'default', 0);
	 	 $configModel->saveConfig('configswatches/general/swatch_attributes', "", 'default', 0);
	 }
	
	/**
	* * Capture Offline payment
	* */
	public function capture_offline($orderId, $invoiceId)
	{
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		if (!$order->canCreditmemo()) {
			if ($invoiceId) {
				$invoice      = Mage::getModel('sales/order_invoice')->loadByIncrementId($invoiceId)->setOrder($order);

				$capture_case = 'offline';
				$invoice->setRequestedCaptureCase($capture_case)->setCanVoidFlag(false)->pay();
				
				$transactionSave = Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder());
				$transactionSave->save();
				$this->order = Mage::getSingleton('sales/order_api');
				$this->order->addComment($orderId, 'processing', 'Captured Offlined successfully', false);
				return true;
			}
		} else {
			return false;
		}
	}
	
	/**
	* * Check invoice exists or not
	* * @param = order id
	* * return true or false
	* */
	public function check_invoice_exists($orderId)
	{
		$invoiceTable = Mage::getSingleton('core/resource')->getTableName('sales_flat_invoice');
		$order        = Mage::getModel('sales/order')->loadByIncrementId($orderId);
		$Id           = $order->getId();
		$sql          = "select entity_id from " . $invoiceTable . "
							where order_id = '" . (int) $Id . "'";

		$invoice = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchRow($sql);
		
		if (!empty($invoice)) {
			return true;
		} else {
			return false;
		}
	}

	/** 
	* * Create Order invoice *
	* **/
	public function create_invoice($order_id, $info, $message, $send_email, $include_comment)
	{
		$this->invoice = Mage::getSingleton('sales/order_invoice_api');
		$invoice       = $this->invoice->create($order_id, $info, $message, $send_email, $include_comment);
		if ($invoice) {
			$this->order = Mage::getSingleton('sales/order_api');
			$this->order->addComment($order_id, 'processing', 'Invoice Created Successfully', false);
		}
		return $invoice;
	}
	
	/**
	* * function to get total product count ( type wise eg: simple, configured )
	 *
	 * * @params $type string Magento Product type eg: simple, configured
	 * @params $modifiedAfter date product last modified date filter
	 * * @return int number of products
	 * */
	function get_total_prod_count($type, $modifiedAfter = null)
	{
		$collection = Mage::getModel('catalog/product')
						->getCollection()
						->addFieldToFilter('type_id', $type);
						
		if (!empty($modifiedAfter)) {
			$collection->addAttributeToFilter('updated_at', array('gteq' => $modifiedAfter));
		}
		return $collection->getSize();
	}
	
	/**
	* * function to get product collection with limit
	 *
	 * * @params $type int Magento Product type Id
	 * * @params $offset int set current page
	 * * @params $limit int products per page
	 * * @params $modifiedAfter date product last modified date filter
	 * * @return array products or bool(false)
	 * */
	function get_product_collection($type, $offset = 1, $limit = 10, $modifiedAfter = null, $createdAfter = null)
	{
		$data = array();
	/*	Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		
		$collection = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('*') // select all attributes
			->addFieldToFilter('type_id', $type);
*/
		$storeId = Mage::app()->getStore()->getStoreId();

		$collection = Mage::getResourceModel('catalog/product_collection')
			->addStoreFilter($storeId)
			->addAttributeToSelect('*')
			->addFieldToFilter('type_id', $type);

		if (!empty($modifiedAfter)) {
		$collection->addAttributeToFilter('updated_at', array('gteq' => $modifiedAfter));
		}

		if (!empty($createdAfter)) {
			$collection->addAttributeToFilter('updated_at', array('gteq' => $createdAfter));
        }
		
		$collection->setPageSize($limit) // limit number of results returned //
			->setOrder('entity_id', 'DESC')
			->setCurPage($offset);
		
		foreach ($collection as $product) {
			$stocklevel = Mage::getSingleton('cataloginventory/stock_item')
							->loadByProduct($product)->getData();

			$data[]	= array_merge(
				$product->getData(), 
				array('prod_url'	=> $product->getProductUrl()),
				array('stock'		=> $stocklevel)
			);
		}
		return $data;
	}
	
	/**
	* * Get attribute name By ID
	* * @param attribute id
	* * @return attribute name
	* */
	function get_attr_name_by_id($id)
	{
		$entityTypeId   = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
		$attributeSetId = Mage::getModel('eav/entity_attribute_set')->getCollection()
							->setEntityTypeFilter($entityTypeId)
							->addFieldToFilter('attribute_set_id', $id)
							->getFirstItem()
							->getAttributeSetName();
		if ($attributeSetId) {
			return $attributeSetId;
		} else {
			return false;
		}
	}
	
	/**
	* * return allchildren product of magento product id
	* * @params Magento Product Id
	* * @return product data or bool(false)
	* */
	function get_child_product($entity_id)
	{
		$relTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_relation');
		$sql      = "SELECT child_id FROM  `" . $relTable . "`
						WHERE parent_id = '" . (int) $entity_id . "'";
		$prods    = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);
		if ($prods) {
			return $prods;
		} else {
			return false;
		}
	}
	
	/**
	* check product exists in target store using saleswarp_product_id in magento catalog_product_entity_in  
	* 
	* use this check_product_exists to query store by warehouse fields that are 
	* known to not change (sku changes) 
	* 
	* @param mixed $attr_id - attribute id, get this after config lookup 
	* @param mixed $base_product_id
	* 
	* @todo seperate these functions into extension API once we debug issue with override extensions in Magento 
	*/
	public function check_product_exists($attr_id, $base_product_id)
	{
		$productIntTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_int');
		$sql             = "select entity_id from " . $productIntTable . "
							   where attribute_id = '" . (int) $attr_id . "' 
							   and value = '" . (int) $base_product_id . "'";
		$prod            = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchRow($sql);
		if (!empty($prod)) {
			$pid = $prod['entity_id'];
			unset($prod);
			return $pid;
		} else {
			return false;
		}
	}
	
	 /**
	 * light weight inventory update when product is detected out of stock or discontinued and full 
	 * update info (cogs, weight, price) not longer apply 
	 * 
	 * @param mixed $store_prod_id
	 * @param mixed $store_qty
	 * @param mixed $discontinued
	 * @param mixed $status
	 * @return mixed
	 * */
	function update_inventory($store_prod_id, $store_qty, $discontinued, $attribute_id, $status = 1, $website_id = 1, $stock_id = 1)
	{
		$resource	= Mage::getSingleton('core/resource');
		$invStockItemTable   = $resource->getTableName('cataloginventory_stock_item');
		$invStockStatusTable = $resource->getTableName('cataloginventory_stock_status');
		$prodDateTimeTable   = $resource->getTableName('catalog_product_entity_datetime');
		
		$write 	= $resource->getConnection('core_write');
		$read   = $resource->getConnection('core_read');
		
		if ($store_qty <= 0 || $discontinued == 1) {
			$status = 0;
		} else {
			$status = 1;
		}
		
		if (is_null($store_qty)) {
			$store_qty = 0;
			$status    = 0;
		}
		
		$sql	= "UPDATE " . $invStockItemTable . " 
					SET	qty =  :qty, 
						is_in_stock = :is_in_stock 
					WHERE  product_id = :product_id ";
		$binds	= array(
			'qty'			=> $store_qty,
			'is_in_stock'	=> $status,
			'product_id'	=> $store_prod_id,
		);
		$result = $write->query($sql, $binds);
		
		$sql	= "SELECT * FROM " . $invStockStatusTable . " 
					WHERE  product_id	= :product_id 
					AND    website_id 	= :website_id 
					AND    stock_id 	= :stock_id ";
		$binds	= array(
			'product_id'	=> $store_prod_id,
			'website_id'	=> $website_id,
			'stock_id'		=> $stock_id,
		); 
		$chk = $read->fetchRow($sql, $binds);
		if (empty($chk)) {
			$sql	= "INSERT INTO " . $invStockStatusTable . " 
							SET qty = :qty, 
							stock_status = :stock_status, 
							stock_id = :stock_id, 
							website_id = :website_id, 
							product_id = :product_id ";
							
			$binds	= array(
				'qty'			=> $store_qty,
				'stock_status'	=> $status,
				'stock_id'		=> $stock_id,
				'website_id'	=> $website_id,
				'product_id'	=> $store_prod_id,
			);
			$result2 = $write->query($sql, $binds);
		} else { // do update 
			$sql	= "UPDATE " . $invStockStatusTable . "
							SET	qty = :qty, stock_status = :stock_status 
							WHERE  product_id = :product_id ";
			$binds	= array(
				'qty'			=> $store_qty,
				'stock_status'	=> $status,
				'product_id'	=> $store_prod_id,
			);
			$result2 = $write->query($sql, $binds);
		}
		
		// Update saleswarp_last_inventory_update fields 
		$sql = "SELECT * FROM " . $prodDateTimeTable . " 
					WHERE entity_type_id = 4 
					AND entity_id = $store_prod_id
					AND attribute_id = $attribute_id";
		$chk = $read->fetchAll($sql);
		
		if (empty($chk)) { // insert it 
			$sql	= "INSERT INTO " . $prodDateTimeTable . " 
						SET value = NOW(), 
						entity_type_id = 4,
						store_id = 0,  
						entity_id = $store_prod_id, 
						attribute_id = $attribute_id";
		$insert_result = $write->query($sql);
		} else { // update it 
		$sql	= "UPDATE " . $prodDateTimeTable . " 
						SET value = NOW()
						WHERE entity_type_id = 4 
						AND entity_id = $store_prod_id
						AND attribute_id = $attribute_id"; 
		$update_result = $write->query($sql);
		}
		
		if ($result && $result2) {
			return "Saved inventory record";
		} else {
			return "FAILED save() inventory update";
		}
	}
}
