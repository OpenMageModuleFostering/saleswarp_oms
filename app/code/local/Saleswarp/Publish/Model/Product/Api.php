<?php
/**
 * @copyright 2011 6th Street Inc, all rights reserved
 * @author David Potts
 * @version 1.0
 * @package SalesWarp
 * @subpackage Product_API
 *
 * @license commercial, unauthorized copies are prohibited
 *
 *
 * Attributes needed in Magento
 *
 * warehouse_id: warehouse_prod_id
 * warehouse_prod_add_date: w_prod_add_date
 * warehouse_prod_code: warehouse_prod_code
 * warehouse_prod_sync_date: w_prod_sync_date
 *
 * supplier_id: supplier_id
 * supplier_name: Seller
 *
 * last_inventory_update: last_inventory_update
 * last_price_update: last_price_update
 * last_update: last_update
 *
 * CREATE WAREHOUSE ATTRIBUTE SET.
 *
 * @todo - set enable_logs and log_filename as API config in system.xml
 *
 *
 */

class Saleswarp_Publish_Model_Product_Api extends Saleswarp_Oms_Model_Product_Api
{
	var $enable_logs = true;
	var $log_level = 2;
	var $log_filename = 'saleswarp_product_api.log';
	var $truncate_test = array('catalog_product_entity');
	var $product_tables = array(
		'catalogindex_eav' => 'entity_id',
		'cataloginventory_stock_item' => 'product_id',
		'cataloginventory_stock_status' => 'product_id',
		'cataloginventory_stock_status_idx' => 'product_id',
		'catalogrule_product' => 'product_id',
		'catalogrule_product_price' => 'product_id',
		'catalogsearch_fulltext' => 'product_id',
		'catalogsearch_result' => 'product_id',
		'catalog_product_enabled_index' => 'product_id',
		'catalog_product_entity' => 'entity_id',
		'catalog_product_entity_datetime' => 'entity_id',
		'catalog_product_entity_decimal' => 'entity_id',
		'catalog_product_entity_gallery' => 'entity_id',
		'catalog_product_entity_int' => 'entity_id',
		'catalog_product_entity_media_gallery' => 'entity_id',
		'catalog_product_entity_text' => 'entity_id',
		'catalog_product_entity_tier_price' => 'entity_id',
		'catalog_product_entity_varchar' => 'entity_id',
		'catalog_product_index_eav' => 'entity_id',
		'catalog_product_index_eav_decimal' => 'entity_id',
		'catalog_product_index_eav_decimal_idx' => 'entity_id',
		'catalog_product_index_eav_idx' => 'entity_id',
		'catalog_product_index_price' => 'entity_id',
		'catalog_product_index_price_bundle_idx' => 'entity_id',
		'catalog_product_index_price_bundle_opt_idx' => 'entity_id',
		'catalog_product_index_price_bundle_sel_idx' => 'entity_id',
		'catalog_product_index_price_cfg_opt_agr_idx' => 'parent_id',
		'catalog_product_index_price_cfg_opt_idx' => 'entity_id',
		'catalog_product_index_price_downlod_idx' => 'entity_id',
		'catalog_product_index_price_final_idx' => 'entity_id',
		'catalog_product_index_price_idx' => 'entity_id',
		'catalog_product_index_price_opt_agr_idx' => 'entity_id',
		'catalog_product_index_price_opt_idx' => 'entity_id',
		'catalog_product_index_tier_price' => 'entity_id',
		'catalog_product_relation' => 'child_id',
		'catalog_product_website' => 'product_id',
		'catalog_category_product' => 'product_id',
		'catalog_category_product_index' => 'product_id',
		'catalog_compare_item' => 'product_id'
	);

    var $product_required_storeIds = array(
		'catalog_product_entity_datetime' => '0',
		'catalog_product_entity_decimal' => '0',
		'catalog_product_entity_int' => '0',
		'catalog_product_entity_text' => '0',
		'catalog_product_entity_varchar' => '0',
	);

    var $product_required_websiteIds = array(
		'catalog_product_entity_tier_price' => '1',
		'catalog_product_index_price' => '1',
		'catalog_product_index_price_bundle_idx' => '1',
		'catalog_product_index_price_bundle_opt_idx' => '1',
		'catalog_product_index_price_bundle_sel_idx' => '1',
		'catalog_product_index_price_cfg_opt_idx' => '1',
		'catalog_product_index_price_downlod_idx' => '1',
		'catalog_product_index_price_final_idx' => '1',
		'catalog_product_index_price_idx' => '1',
		'catalog_product_index_price_opt_agr_idx' => '1',
		'catalog_product_index_price_opt_idx' => '1',
		'catalog_product_index_tier_price' => '1',
		'catalog_product_website' => '1',
		'cataloginventory_stock_status' => '1',
		'cataloginventory_stock_status_idx' => '1'
	);

	/**
	 * Get Attribute Id By name
	 *
	 */
	public function get_attr_id_by_name($attributeSetName = 'Default')
	{
		$entityTypeId   = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
		//$attributeSetName   = 'Default';
		$attributeSetId = Mage::getModel('eav/entity_attribute_set')->getCollection()->setEntityTypeFilter($entityTypeId)->addFieldToFilter('attribute_set_name', $attributeSetName)->getFirstItem()->getAttributeSetId();
		if ($attributeSetId) {
			return $attributeSetId;
		} else {
			return false;
		}
	}

	/**
	 * Update Product
	 * @param product data array
	 */
	function product_slwp_update($data)
	{
		if (empty($data)) {
			return 'no data';
		}
		$code = 'saleswarp_prod_id';
		foreach ($data as $prod) {
			$prod = $prod['BaseProductsBaseStore'];
			if ($this->check_prod_exists($prod['merchant_sku'])) {
				$this->rest_save_attribute($prod['merchant_sku'], $code, $prod['base_product_id'], 1);
			}
		}
		return "success";
	}


	/**
	 * Read and populate product entity
	 */
	function product_list($type = 'simple', $offset = 0, $limit = 50000)
	{
		$productTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity');
		$sql          = "select entity_id,sku,attribute_set_id from " . $productTable . "
							where type_id = '" . $type . "'
							order by entity_id asc limit $offset,$limit";
		$data         = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);
		return $data;
	}



	/**
	 * Check if Configurable Product Exists
	 *
	 **/
	function check_configurable_option_exists($attribute_code, $saleswp_option)
	{
		$read  = Mage::getSingleton('core/resource')->getConnection('core_read');
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');

		$eavAttrOptValTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute_option_value');
		$eavAttrOptTable    = Mage::getSingleton('core/resource')->getTableName('eav_attribute_option');
		$eavAttrTable       = Mage::getSingleton('core/resource')->getTableName('eav_attribute');

		$sql_option = "SELECT b.option_id FROM " . $eavAttrOptValTable . " as a
                        JOIN " . $eavAttrOptTable . " as b ON  a.option_id = b.option_id
                        JOIN " . $eavAttrTable . " as c ON b.attribute_id = c.attribute_id
                        WHERE a.value = :saleswp_option AND c.attribute_code = :attribute_code ";
		$binds      = array(
			'saleswp_option' => $saleswp_option,
			'attribute_code' => $attribute_code
		);

		$connection = $read->fetchAll($sql_option, $binds);

		if (!empty($connection)) {
			$value = $connection[0]['option_id'];
		} else {
			$attr_model = Mage::getModel('catalog/resource_eav_attribute');
			$attr       = $attr_model->loadByCode('catalog_product', $attribute_code);

			$attr_id = $attr->getAttributeId();

			$option = array();

			$sql = 'INSERT INTO ' . $eavAttrOptTable . '(attribute_id, sort_order) VALUES (' . (int) $attr_id . ',0)';
			$write->query($sql);

			$lastInsertId = $write->lastInsertId();

			$sql2                 = "INSERT INTO " . $eavAttrOptValTable . "(option_id, store_id , value) VALUES (:lastInsertId, 0, :saleswp_option)";
			$binds2               = array(
				'lastInsertId' => $lastInsertId,
				'saleswp_option' => $saleswp_option
			);
			$attr_value_insertion = $write->query($sql2, $binds2);

			$value = $lastInsertId;
		}

		return $value;
	}

	/**
	 * Create Product Option in MAGENTO
	 */
	function add_product_option($product_id, $attribute_code, $value)
	{
		$product = Mage::getModel("catalog/product")->load($product_id);
		$product->setData($attribute_code, $value);
		if($product->save()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Create Configurable Attributes in magento
	 */
	function create_configurable_attribute($code, $label, $attribute_type, $product_type)
	{
		$_attribute_data = array(
			'attribute_code' => $code,
			'is_global' => '1',
			'frontend_input' => $attribute_type, //'boolean',
			'default_value_text' => '',
			'default_value_yesno' => '0',
			'default_value_date' => '',
			'default_value_textarea' => '',
			'is_unique' => '1',
			'is_required' => '0',
			'apply_to' => $product_type, //array('grouped')
			'is_configurable' => '1',
			'is_searchable' => '1',
			'is_filterable' => '1',
			'is_visible_in_advanced_search' => '0',
			'is_comparable' => '0',
			'is_used_for_price_rules' => '0',
			'is_wysiwyg_enabled' => '0',
			'is_html_allowed_on_front' => '1',
			'is_visible_on_front' => '0',
			'used_in_product_listing' => '0',
			'used_for_sort_by' => '0',
			'frontend_label' => array(
				$label
			)
		);

		$model = Mage::getModel('catalog/resource_eav_attribute');


		if (is_null($model->getIsUserDefined()) || $model->getIsUserDefined() != 0) {
			$_attribute_data['backend_type'] = $model->getBackendTypeByInput($_attribute_data['frontend_input']);
		}

		$defaultValueField = $model->getDefaultValueByInput($_attribute_data['frontend_input']);

		if ($defaultValueField) {
			$_attribute_data['default_value'] = $this->getRequest()->getParam($defaultValueField);
		}

		$model->addData($_attribute_data);
		$model->setEntityTypeId(Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId());
		$model->setIsUserDefined(1);

		try {
			$model->save();
			return true;
		}
		catch (Exception $e) {
			$msg = "error : " . $e->getMessage();
			Mage::log($msg, null, "product_api.log", true);
			$this->_fault($msg, $e->getMessage());
			return false;
		}
	}

	/**
	 * Create Configure Product
	 */
	function create_product_configurable($configurable_product_id, $configurable_child_id)
	{
		$msg = "Parent conf Id:" . $configurable_product_id;
		Mage::log($msg, $this->log_level, $this->log_filename);
		$config_product = Mage::getModel('catalog/product')->load($configurable_product_id);
		$new_ids        = array();
		$used_products  = $config_product->getTypeInstance()->getUsedProductIds();

		foreach ($used_products as $used_product) {
			$current_ids[] = $used_product;
		}

		$current_ids[] = $configurable_child_id;
		$current_ids   = array_unique($current_ids);

		foreach ($current_ids as $temp_id) {
			parse_str("position=", $new_ids[$temp_id]);
		}
		$config_product->setConfigurableProductsData($new_ids)->save();
	}

	/**
	 * Create Configure Product super attributes
	 *
	 * @params $data array super attribute data array
	 *
	 * Note :: This is not the magento recomended way, as there are alot of API calls eecuted in order to save the
	 * single configurable product. Therefore writing the custom query in order to achieve the expected functionality.
	 */
	public function create_configurable_product_super_attribute($data) {
		$return 	= [];

		foreach($data as $sku => $super_attributes) {
			$product_id = Mage::getModel('catalog/product')->getIdBySku($sku);
			if($product_id) {
				$return[$product_id] = [];
				foreach($super_attributes as $code => $pricing) {
					$attribute		= Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $code);
					$attribute_id	= $attribute->getId();

					if($attribute_id) {
						$return[$product_id][$code]['attribute_id']	= $attribute_id;
						$values  = Mage::getResourceModel('eav/entity_attribute_option_collection')
									->setStoreFilter($storeId)
									->setAttributeFilter($attribute_id)
									->toOptionArray();

						$pricing = array_change_key_case($pricing);

						foreach($values as $value) {
							if(isset($pricing[strtolower($value['label'])])) {
								$return[$product_id][$code]['pricing'][$value['value']]	= $pricing[strtolower($value['label'])];
							}
						}
					}
				}
			} else {
				$return[$sku] = 'Product not found';
			}
		}

		foreach($return as $product_id => $attributes) {
			foreach($attributes as $code	=> $attribute) {
				$attribute_id	= $attribute['attribute_id'];
				$product_super_attribute_id	= $this->getProductSuperAttributeId($product_id, $attribute_id);

				$value_id	= $this->createCatalogProductSuperAttributeLabel($product_super_attribute_id, $code);

				foreach($attribute['pricing'] as $value_index => $pricing_value) {
					$this->createCatalogProductSuperAttributePricing($product_super_attribute_id, $value_index, $pricing_value);
				}
			}
		}
		return $return;
	}


	/**
	 * Inserting the pricing values corresponding to the configurable attributes
	 *
	 * @params $product_super_attribute_id int  magento configurable product super attribute id
	 * @params $value_index int magento product attribute option id
	 * @params $pricing_value int magento configurable product price value
	 *
	 * Note :: This is not the magento recomended way, as there are alot of API calls eecuted in order to save the
	 * single configurable product. Therefore writing the custom query in order to achieve the expected functionality.
	 */
	private function createCatalogProductSuperAttributePricing($product_super_attribute_id, $value_index, $pricing_value) {
		$resource	= Mage::getSingleton('core/resource');
		$dbSource	= $resource->getConnection('core_write');

		$catalog_product_super_attribute_pricing = $resource->getTableName('catalog_product_super_attribute_pricing');

		$sql	= "SELECT * FROM `" . $catalog_product_super_attribute_pricing . "`
							WHERE `product_super_attribute_id` = '" . $product_super_attribute_id . "'
							and value_index = '" . $value_index . "' ";
		$value_id   = $dbSource->fetchOne($sql);

		if($value_id) {
			$sql	= "update " . $catalog_product_super_attribute_pricing . "
						set pricing_value	= '".$pricing_value."'
						WHERE `product_super_attribute_id` = '" . $product_super_attribute_id . "'
							and value_index = '" . $value_index . "' ";
			$dbSource->query($sql);
		} else {
			$sql	= "Insert into " . $catalog_product_super_attribute_pricing . "(product_super_attribute_id, value_index, is_percent, pricing_value, website_id)
			values('".$product_super_attribute_id."', '".$value_index."', '0', '".$pricing_value."', '0')";
			$dbSource->query($sql);
		}
	}

	/**
	 * Get the configurable product supper attribute id
	 *
	 * @params $product_id int  magento product id
	 * @params $attribute_id int magento product attribute id
	 *
	 * Note :: This is not the magento recomended way, as there are alot of API calls eecuted in order to save the
	 * single configurable product. Therefore writing the custom query in order to achieve the expected functionality.
	 */
	private function getProductSuperAttributeId($product_id, $attribute_id) {
		$resource	= Mage::getSingleton('core/resource');
		$dbSource	= $resource->getConnection('core_write');

		$catalog_product_super_attribute = $resource->getTableName('catalog_product_super_attribute');

		$sql	= "SELECT * FROM `" . $catalog_product_super_attribute . "`
							WHERE `product_id` = '" . $product_id . "'
							and attribute_id = '" . $attribute_id . "' ";
		$product_super_attribute_id   = $dbSource->fetchOne($sql);
		if($product_super_attribute_id) {
			return $product_super_attribute_id;
		} else {
			$sql	= "Insert into " . $catalog_product_super_attribute . "(product_id, attribute_id, position) values('".$product_id."', '".$attribute_id."', '0')";
			$dbSource->query($sql);

			$sql	= "SELECT * FROM `" . $catalog_product_super_attribute . "`
							WHERE `product_id` = '" . $product_id . "'
							and attribute_id = '" . $attribute_id . "' ";
			return $dbSource->fetchOne($sql);
		}
	}

	/**
	 * Generating configurable product supper attribute label
	 *
	 * @params $product_super_attribute_id int  magento configurable product super atribute id
	 * @params $value string attribute label
	 *
	 * Note :: This is not the magento recomended way, as there are alot of API calls eecuted in order to save the
	 * single configurable product. Therefore writing the custom query in order to achieve the expected functionality.
	 */
	private function createCatalogProductSuperAttributeLabel($product_super_attribute_id, $value) {
		$resource	= Mage::getSingleton('core/resource');
		$dbSource	= $resource->getConnection('core_write');

		$catalog_product_super_attribute_label = $resource->getTableName('catalog_product_super_attribute_label');

		$sql	= "SELECT * FROM `" . $catalog_product_super_attribute_label . "`
							WHERE `product_super_attribute_id` = '" . $product_super_attribute_id . "'
							and value = '" . $value . "' ";
		$value_id   = $dbSource->fetchOne($sql);
		if($value_id) {
			return $value_id;
		} else {
			$sql	= "Insert into " . $catalog_product_super_attribute_label . "(product_super_attribute_id, store_id, value) values('".$product_super_attribute_id."', '0', '".$value."')";
			$dbSource->query($sql);
		}
	}

	/**
	 * Check Attribute Exists
	 */
	function check_attribute_exists($attr_code)
	{
		$attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attr_code);

		if ($attribute) {
			return $attribute->getData('attribute_id');
		} else {
			return false;
		}
	}

	/**
	 * a comprehensive delete function for products, checks all related tables for hanging entity_ids and
	 * does full clean
	 *
	 * relies on array of tables & corresponding product key; usually product_id or entity_id. affected
	 * tables varies by both Magento release and customizations to the store, ie. if a table is added
	 * by any extensions, it can be cleaned up easily through delete_full
	 *
	 * @param mixed $productId
	 * @param mixed $identifierType
	 **/
	public function delete_full($productId, $identifierType = null)
	// load array of tables & fields to check for matching data
	{
		$write    = Mage::getSingleton('core/resource')->getConnection('core_write');
		$datetime = strftime('%Y-%m-%d %H:%M:%S', time());
		$cnt      = 0;
		foreach ($this->product_tables as $table => $field) {
			$table = Mage::getSingleton('core/resource')->getTableName($table);
			$sql   = "DELETE FROM " . $table . " WHERE " . $field . " = '" . (int) $productId . "'";
			$msg   = "Calling delete_full: sql = " . $sql;
			Mage::log($msg, null, "product_api.log");
			try {
				$result = $write->query($sql);
				$cnt++;
			}
			catch (Mage_Core_Exception $e) {
				$msg = "error : " . $e->getMessage();
				Mage::log($msg, null, "product_api.log", true);
				$msg = 'inside delete full, not_deleted on table: ' . $table . ', product_id: ' . $productId;
				$this->_fault($msg, $e->getMessage());
				Mage::log($msg, null, "product_api.log", true);
			}
		}
		return $cnt;
	}

	/**
	 * Add new attribute to a set
	 */
	function add_configurable_attribute_to_default($attribute_set_name, $group_name, $attribute_code)
	{
		$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

		//-------------- add attribute to set and group
		$attribute_set_id   = $setup->getAttributeSetId('catalog_product', $attribute_set_name);
		$attribute_group_id = $setup->getAttributeGroupId('catalog_product', $attribute_set_id, $group_name);
		$attribute_id       = $setup->getAttributeId('catalog_product', $attribute_code);
		$setup->addAttributeToSet('catalog_product', $attribute_set_id, $attribute_group_id, $attribute_id);
	}

	/**
	 * To check configured product exists or not
	 */
	public function check_configured_exists($attr_id, $base_product_id)
	{
		$productTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity');
		$sql          = "select m.entity_id,m.type_id from " . $productTable . " m
							left join catalog_product_entity_int mi ON m.entity_id = mi.entity_id
							where mi.attribute_id = '" . (int) $attr_id . "'
							and mi.value = '" . (int) $base_product_id . "'";
		$prod         = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchRow($sql);

		if (!empty($prod)) {
			$pid = $prod['entity_id'];
			unset($prod);
			return $pid;
		} else {
			return false;
		}
	}

	/**
	 * public function to check configured product exists or not
	 */
	public function check_configured_exists_by_group_string($attr_id, $groupString)
	{
		$productTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity');
		$sql          = "SELECT m.entity_id,m.type_id FROM " . $productTable . " m
							LEFT JOIN catalog_product_entity_text mi ON m.entity_id = mi.entity_id
							WHERE mi.attribute_id = :attr_id  AND mi.value = :groupString ";
		$binds        = array(
			'attr_id' => $attr_id,
			'groupString' => $groupString
		);
		$prod         = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchRow($sql, $binds);

		if (!empty($prod)) {
			$pid = $prod['entity_id'];
			unset($prod);
			return $pid;
		} else {
			return false;
		}
	}

	/**
	 * update update_category_product table with this product_id and category_id
	 *
	 * @param mixed $entity_id
	 * @param int|array $cat_ids
	 */
	public function update_category_product($entity_id, $cat_ids)
	{
		// Ensure $cat_ids is an array because we may receive an array
		// No matter what, we're only dealing with the primary category here, and assuming its index is 0
		if (!is_array($cat_ids)) {
			$cat_ids = array($cat_ids);
		}

		$categoryProdTable = Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
		foreach ($cat_ids as $cat_id) {
			$sql  = sprintf("select * from " . $categoryProdTable . " WHERE product_id = '%d' AND  category_id = '%d';", $entity_id, $cat_id);
			$prod = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);
			if (!empty($prod)) {
				return true;
			} else {
				$write    = Mage::getSingleton('core/resource')->getConnection('core_write');
				$datetime = strftime('%Y-%m-%d %H:%M:%S', time());
				$sql      = sprintf("INSERT INTO `" . $categoryProdTable . "` (product_id, category_id, position) VALUES ('%d', '%d', 0);", $entity_id, $cat_id);
				$result   = $write->query($sql);
			}
		}
		return true;
	}

	/**
	 * update the catalog_category_product_index tables for this product and category
	 *
	 * @param mixed $prod_id
	 * @param int|array $cat_ids
	 * @param mixed $store_id
	 */
	public function update_cat_prod_indx($prod_id, $cat_ids, $store_id = 1)
	{
		// $cat_ids will be assumed to be an array
		if (!is_array($cat_ids)) {
			$cat_ids = array($cat_ids);
		}

		$categoryTable     = Mage::getSingleton('core/resource')->getTableName('catalog_category_entity');
		$categoryIndxTable = Mage::getSingleton('core/resource')->getTableName('catalog_category_product_index');
		$sql               = sprintf("select * from " . $categoryTable . " WHERE entity_id ='%d' LIMIT 1", $cat_ids[0]);
		$prod              = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);

		if (empty($prod)) {
			echo "ERROR Category-Entity NOT found. \n";
			return false;
		}

		$path1 = explode('/', $prod[0]['path']);

		foreach ($path1 as $path) {
			if ($path != 1) {
				$is_parent = ($path == $cat_ids[0]) ? 1 : 0;

				$sql  = sprintf("select * from " . $categoryIndxTable . " WHERE product_id = '%d' AND category_id = '%d' AND store_id = '%d'", $prod_id, $path, $store_id);
				$prod = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);

				if (!empty($prod)) {
					// skip
					echo "Category-Product-Index  confirmed. <br>\n";
				} else {
					// echo "no existing Category-Product found, continuing insert. <br>\n";
					$w = Mage::getSingleton('core/resource')->getConnection('core_write');

					$datetime = strftime('%Y-%m-%d %H:%M:%S', time());
					$sql      = "INSERT INTO `" . $categoryIndxTable . "` (product_id, category_id, position, store_id, is_parent, visibility) VALUES (%d, %d, 0, %d, %d, 4);";
					$result   = $w->query(sprintf($sql, $prod_id, $path, $store_id, $is_parent));
				}
			} // end $path !=1
		}
		return true;
	}

	/**
	 * Create New Product in magento
	 */
	public function create_fast($type, $attr_set_id, $sku, $productData, $qty, $websites, $cat_id, $store = 0)
	{
		$this->log_filename = 'create_fast.log';
		if ($this->enable_logs) {
			$starttime = time();
			$msg       = "Called saleswarp_product_api -> create_fast";
			$msg .= "\n TIMESTAMP CREATE START: " . time();
			Mage::log($msg, $this->log_level, $this->log_filename);
		}

		$msg = "prod type=" . $type;
		Mage::log($msg, $this->log_level, $this->log_filename);

		try {
			/**
			 *   $out = array('status'=>"pass",
			 'id'=>$entity_id,
			 'msg'=>"passed insert into catalog_product_entity");
			 *
			 * @var mixed
			 */
			$result = $this->fast_save($type, $sku, $productData, $qty, $attr_set_id, $websites, $cat_id);

			// update category_ids
			if ($result['status'] == "pass") {
				$new_id = $result['id'];
				$this->update_category_ids($new_id, $cat_id);
			} else {
				return $result;
			}

			if ($this->enable_logs) {
				$msg = "After SAVE TIMESTAMP: " . time();
				$msg .= "\n ELAPSE TIME: " . (time() - $starttime);
				Mage::log($msg, $this->log_level, $this->log_filename);
			}

		}
		catch (Mage_Core_Exception $e) {
			if ($this->enable_logs) {
				$msg = "EXCEPTION CAUGHT in CREATE_FAST. ErrorMsg = " . $e->getMessage();
				Mage::log($msg, $this->log_level, $this->log_filename);
			}
			$this->_fault('data_invalid', $e->getMessage());
		}
		return $result;
	}

	/**
	 * use this to assign product to single or muliple category_ids
	 */
	function update_category_ids($store_product_id, $category_ids)
	{
		$w = Mage::getSingleton('core/resource')->getConnection('core_write');

		// Ensure we have an array for category_ids
		if (!$category_ids) {
			return false;
		}

		if (!is_array($category_ids)) {
			$category_ids = array($category_ids);
		}

		$productTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity');
		$sql          = "UPDATE `" . $productTable . "`
							 SET `saleswarp_category_ids` = '" . (int) $category_ids[0] . "'
							 WHERE `entity_id` = '" . (int) $store_product_id . "'";
		$result       = $w->query($sql);
		if (!$result) {
			return false;
		}

		// Update Catalog_Category_Product
		$r1 = $this->update_category_product($store_product_id, $category_ids);

		// Update Catalog_Category_Product_Indx
		$r2 = $this->update_cat_prod_indx($store_product_id, $category_ids);

		return $r1 && $r2;
	}

	/**
	 * Update Product Qty to 1
	 *
	 */
	function set_in_stock($store_product_id, $stock_id, $status)
	{
		$invStockItemTable = Mage::getSingleton('core/resource')->getTableName('cataloginventory_stock_item');
		$sql               = "UPDATE " . $invStockItemTable . " SET is_in_stock = '1' WHERE product_id = '" . (int) $store_product_id . "'";
		$connection        = Mage::getSingleton('core/resource')->getConnection('core_write');
		$connection->query($sql);
		return true;
	}

	/**
	 * function get attibute details
	 */
	function get_attribute_details($attribute_code)
	{
		$attribute_details = Mage::getSingleton("eav/config")->getAttribute("catalog_product", $attribute_code);
		$options           = $attribute_details->getSource()->getAllOptions(false);
		return $options;
	}

	/**
	 * Set Product ATTRIBUTE
	 */
	function set_product_config_attr($product_id, $configAttrCodes)
	{
		$configProduct    = Mage::getModel("catalog/product")->load($product_id);
		$attr_ids	= [];
		foreach ($configAttrCodes as $attrCode) {
			$super_attribute= Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attrCode);
			$attr_ids[]		= $super_attribute->getId();
		}
		$configProduct->getTypeInstance()->setUsedProductAttributeIds($attr_ids);
		$configurableAttributesData = $configProduct->getTypeInstance()->getConfigurableAttributesAsArray();
		$configProduct->setCanSaveConfigurableAttributes(true);
		$configProduct->setConfigurableAttributesData($configurableAttributesData);
		$configProduct->save();
		return true;
	}

	/**
	 * Save Product Data
	 */
	function fast_save($product_type, $sku, $data, $qty = 0, $attribute_set_id = 4, $websites, $cat_ids, $store_id = 1, $entity_type_id = 10)
	{
		$entity_type_id = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
		if (!is_array($cat_ids)) {
			$cat_ids = array(
				$cat_ids
			);
		}

		$w = Mage::getSingleton('core/resource')->getConnection('core_write');

		$out      = array(
			'status' => "fail",
			'id' => 0,
			'msg' => ""
		);
		$datetime = strftime('%Y-%m-%d %H:%M:%S', time());

		if ($this->enable_logs) {
			$msg = "Inside fast_save, calling first catalog_product_entity insert";
			;
			Mage::log($msg, $this->log_level, $this->log_filename);
		}

		$productTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity');

		$sql = "INSERT INTO `" . $productTable . "` (entity_id, entity_type_id, attribute_set_id,
        type_id, sku, saleswarp_category_ids, created_at, updated_at, has_options) VALUES (NULL, %d, %d, '%s', '%s', %d, '%s', '%s', 0);";

		$sql = sprintf($sql, $entity_type_id, $attribute_set_id, $product_type, $sku, $cat_ids[0], $datetime, $datetime);

		Mage::log($sql, $this->log_level, $this->log_filename);
		$result = $w->query($sql);

		$result = $w->query(sprintf("select entity_id from " . $productTable . " where sku ='%s';", $sku));
		if (!$result) {
			if ($this->enable_logs) {
				$msg = "FAILED: INSERT PRODUCT INTO CATALOG_PRODUCT ENTITY FAILED";
				Mage::log($msg, $this->log_level, $this->log_filename);
			}
			return $out;
		} else {
			$row       = $result->fetch(PDO::FETCH_ASSOC);
			$entity_id = $row['entity_id'];
			$out       = array(
				'status' => "pass",
				'id' => $entity_id,
				'msg' => "passed insert into catalog_product_entity"
			);
		}

		if ($this->enable_logs) {
			$msg = "PASSED: INSERT PRODUCT INTO CATALOG_PRODUCT ENTITY ";
			Mage::log($msg, $this->log_level, $this->log_filename);
		}

		if ($entity_id == null) {
			var_dump($row);
			die('epic fail');
		}
		$eavAttrTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute');

		foreach ($data as $code => $value) {
			switch ($code) {
				// Add special groups here
				case "age_groups":
					foreach ($value as $v) {
						$code   = "age_group";
						$result = $w->query("SELECT attribute_id, backend_type, frontend_input
                                    FROM `" . $eavAttrTable . "` WHERE `attribute_code` LIKE '" . $code . "'
                                    AND entity_type_id = '" . $entity_type_id . "'");
						$row    = $result->fetch(PDO::FETCH_ASSOC);

						$result = $this->save_attribute_multiselect($row, $entity_id, $entity_type_id, $code, $v, $store_id);
						if ($result != "pass") {
							$out = array(
								'status' => "fail",
								'id' => $entity_id,
								'msg' => "failed during age_group attribute save"
							);
							return $out;
						}
					}
					break;
				default:
					if ($this->enable_logs) {
						$msg = "Inserting attributes: attribute_code = " . $code . ", value = " . $value . ", entity_type_id = " . $entity_type_id;
						Mage::log($msg, $this->log_level, $this->log_filename);
					}

					$result = $w->query("SELECT attribute_id, backend_type, frontend_input, is_user_defined FROM `" . $eavAttrTable . "`
                                         WHERE `attribute_code` LIKE '" . $code . "'
                                         AND entity_type_id = '" . $entity_type_id . "'");
					$row    = $result->fetch(PDO::FETCH_ASSOC);


					if ($row['frontend_input'] == 'select') {
						$result = $this->save_attribute_select($row, $entity_id, $entity_type_id, $code, $value, $store_id, $row['is_user_defined']);

						if ($result != "pass") {
							$out = array(
								'status' => "fail",
								'id' => $entity_id,
								'msg' => "failed during save_attribute_select for code: $code; value: $value"
							);
							return $out;
						}

					} elseif ($row['frontend_input'] == 'multiselect') {
						$result = $this->save_attribute_multiselect($row, $entity_id, $entity_type_id, $code, $value, $store_id);
						if ($result != "pass") {
							$out = array(
								'status' => "fail",
								'id' => $entity_id,
								'msg' => "failed during save_attribute_multiselect for code: $code; value: $value"
							);
							return $out;
						}
					} else {
						$result = $this->save_attribute($row, $entity_id, $entity_type_id, $code, $value, $store_id);
						if ($result != "pass") {
							$out = array(
								'status' => "fail",
								'id' => $entity_id,
								'msg' => "failed during save_attribute for code: $code; value: $value"
							);
							return $out;
						}
					}
					break;

			}
		}

		$invStockItemTable = Mage::getSingleton('core/resource')->getTableName('cataloginventory_stock_item');

		if ($qty > 0) {
			try {
				if ($this->enable_logs) {
					$msg = "calling cataloginventory_stock_item insert";
					;
					Mage::log($msg, $this->log_level, $this->log_filename);
				}

				$w->query("INSERT INTO `" . $invStockItemTable . "`
                    (`product_id` ,`stock_id`,qty,
                    is_in_stock,
                    manage_stock,
                    use_config_min_qty,
                    use_config_min_sale_qty,
                    use_config_backorders,
                    use_config_notify_stock_qty,
                    use_config_manage_stock)VALUES($entity_id,1,$qty,1,1,1,1,1,1,0)");

				$this->update_inventory($entity_id, $qty, 0, 1, 1, 1);
			}
			catch (Exception $e) {
				if ($this->enable_logs) {
					$msg = "EXCEPTION CAUGHT in FAST_SAVE, cataloginventory_stock_item QTY > 0. ErrorMsg = " . $e->getMessage();
					Mage::log($msg, $this->log_level, $this->log_filename);
				}
				$out = array(
					'status' => "fail",
					'id' => $entity_id,
					'msg' => "failed during update_inventory, Exception " . $e->getMessage()
				);
				return $out;
			}
		} else {
			try {
				$w->query("INSERT INTO `" . $invStockItemTable . "`
                    (`product_id` ,`stock_id`,qty,is_in_stock,manage_stock,use_config_min_qty,
                    use_config_min_sale_qty, use_config_backorders, use_config_notify_stock_qty,
                    use_config_manage_stock)VALUES($entity_id,1,$qty,0,1,1,1,1,1,0)");

				$this->update_inventory($entity_id, 0, 0, 1, 1, 1);
			}
			catch (Exception $e) {
				if ($this->enable_logs) {
					$msg = "EXCEPTION CAUGHT in FAST_SAVE, cataloginventory_stock_item QTY = 0. ErrorMsg = " . $e->getMessage();
					Mage::log($msg, $this->log_level, $this->log_filename);
				}
				$out = array(
					'status' => "fail",
					'id' => $entity_id,
					'msg' => "failed during update_inventory, Exception " . $e->getMessage()
				);
				return $out;
			}
		}

		try {
			foreach ($websites as $website_id) {
				// sometimes the entity_id & website_id combo already exists as Magento
				// does not do a good job cleaning via Magento Admin, check for existing
				// and reuse it if it exists
				if ($this->enable_logs) {
					$msg = "checking for catalog_product_website exists for product_id: " . $entity_id . ", website_id = " . $website_id;
					Mage::log($msg, $this->log_level, $this->log_filename);
				}
				$webTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_website');
				$sql      = "select * from
                    " . $webTable . "
                    WHERE product_id = '" . (int) $entity_id . "'
                    AND  website_id = '" . (int) $website_id . "'";
				$chk10    = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);

				if (empty($chk10)) {
					if ($this->enable_logs) {
						$msg = "calling catalog_product_website insert";
						Mage::log($msg, $this->log_level, $this->log_filename);
					}

					$w->query("INSERT INTO `" . $webTable . "` (`product_id`,`website_id`)VALUES($entity_id, $website_id)");
				}
			}
		}
		catch (Exception $e) {
			if ($this->enable_logs) {
				$msg = "EXCEPTION CAUGHT in FAST_SAVE, catalog_product_website. ErrorMsg = " . $e->getMessage();
				Mage::log($msg, $this->log_level, $this->log_filename);
			}
			$out = array(
				'status' => "fail",
				'id' => $entity_id,
				'msg' => "failed during Website update, Exception " . $e->getMessage()
			);
			return $out;
		}
		return $out;
	}

	/**
	 * get media image gallery for a entity id
	 * @params $entityId int Magento Product Id
	 * @return product data or bool(false)
	 */
	function get_product_image_collection($entityId)
	{
		$data       = array();
		$collection = Mage::getModel('catalog/product')->load($entityId)->getMediaGalleryImages();
		if ($collection) {
			foreach ($collection as $img) {
				$data[] = $img->getData();
			}
			return $data;
		}
		return false;
	}

	/**
	 * get category ids for a entity id
	 *
	 * @params $entityId int Magento Product Id
	 * @return array category ids
	 */
	function get_category_for_product($entityId)
	{
		$prod   = Mage::getModel('catalog/product')->load($entityId);
		$catIds = $prod->getCategoryIds();
		return $catIds;
	}

	/**
	 * get quantity by entity id
	 *
	 * @params $entityId int Magento Product Id
	 * @return int quantity of product
	 */
	function get_qty_by_product($entityId)
	{
		$prod = Mage::getModel('catalog/product')->load($entityId);
		$qty  = Mage::getModel('cataloginventory/stock_item')->loadByProduct($prod)->getQty();
		return $qty;
	}



	/**
	 * function to get product by sku
	 *
	 * @params $mageSku string Magento Product Sku
	 * @return array products data
	 */
	function getProductBySKU($mageSku)
	{
		$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $mageSku);
		$data    = $product->getData();
		return $data;
	}



	/**
	 * check product exists in magento
	 *
	 * @params $entity_id int Magento Product id
	 * @return boolean
	 */
	function check_prod_exists($entity_id)
	{
		$productTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity');
		$sql          = "select entity_id from " . $productTable . "
							where entity_id = '" . (int) $entity_id . "'";
		$prod         = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchRow($sql);
		if ($prod) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Save product attribute value
	 *
	 * @params $entity_id int Magento Product id
	 * @params $code string Magento product attribute code
	 * @params $value string Magento product attribute value
	 * @params $store_id int Magento Product store id
	 * @return boolean
	 */
	function rest_save_attribute($entity_id, $code, $value, $store_id)
	{
		$entity_type_id = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
		$eavAttrTable   = Mage::getSingleton('core/resource')->getTableName('eav_attribute');
		$read           = Mage::getSingleton('core/resource')->getConnection('core_read');

		$sql = "SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
								WHERE `attribute_code` LIKE :code
								AND entity_type_id = :entity_type_id ";

		$binds = array(
			'code' => $code,
			'entity_type_id' => $entity_type_id
		);

		$result = $read->query($sql, $binds);

		$row = $result->fetch(PDO::FETCH_ASSOC);
		return $this->save_attribute($row, $entity_id, $entity_type_id, $code, $value, $store_id);
	}

	/**
	 * special attribute saves for select and multiselect types (frontend_type in Magento)
	 *
	 * @params $row string
	 * @params $entity_id string Magento product id
	 * @params $entity_type_id string Magento product type
	 * @params $attribute_code string Magento product attribute code
	 * @params $value string Magento product attribute value
	 * @params $store_id int Magento Product store id
	 * @return boolean
	 */
	function save_attribute($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id)
	{
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$read  = Mage::getSingleton('core/resource')->getConnection('core_read');

		if ((!empty($row)) && ($row['backend_type'] != '') && ($row['backend_type'] != null)) {

			$msg = "Backend_Type = " . $row['backend_type'];
			Mage::log($msg, $this->log_level, $this->log_filename);

			// does this exist
			$productBakTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_' . $row['backend_type']);
			$sql             = "SELECT * FROM  `" . $productBakTable . "`
									WHERE attribute_id = :attribute_id
									AND entity_id = :entity_id
									AND store_id = 0
									AND entity_type_id = :entity_type_id ";
			$binds           = array(
				'attribute_id' => $row['attribute_id'],
				'entity_id' => $entity_id,
				'entity_type_id' => $entity_type_id
			);

			$result = $read->query($sql, $binds);
			$chk    = $result->fetch(PDO::FETCH_ASSOC);

			if (empty($chk)) {
				// If key is in array $this->product_required_storeIds, then create an Admin store_id entry (store_id = 0)
				if (!empty($value) && $value != "") {
					$key1 = "catalog_product_entity_" . $row['backend_type'];


					if (array_key_exists($key1, $this->product_required_storeIds)) {
						$sql = "INSERT INTO `" . $productBakTable . "` (value_id, entity_type_id, attribute_id, store_id, entity_id,value)
									VALUES (NULL ,:etype,:attr,0,:eid,:val) ";

						$binds = array(
							'etype' => $entity_type_id,
							'attr' => $row['attribute_id'],
							'eid' => $entity_id,
							'val' => $value
						);

						$msg = "SQL QUERY = " . $sql;
						Mage::log($msg, $this->log_level, $this->log_filename);
						$write->query($sql, $binds);
					}
				}
			} else { // update
				$sql = "UPDATE `" . $productBakTable . "`
						   SET value = :value
						   WHERE value_id = :value_id ";

				$binds = array(
					'value' => $value,
					'value_id' => $chk['value_id']
				);

				$write->query($sql, $binds);
			}
			// does this exist

			$sql = "SELECT * FROM  `" . $productBakTable . "`
						WHERE attribute_id = :attribute_id
						AND entity_id = :entity_id
						AND store_id = :store_id
						AND entity_type_id = :entity_type_id ";

			$binds = array(
				'attribute_id' => $row['attribute_id'],
				'entity_id' => $entity_id,
				'store_id' => $store_id,
				'entity_type_id' => $entity_type_id
			);

			$result = $write->query($sql, $binds);
			$chk    = $result->fetch(PDO::FETCH_ASSOC);

			if (empty($chk)) {
				if (!empty($value) && $value != "") {
					$sql = "INSERT INTO `" . $productBakTable . "` (value_id, entity_type_id, attribute_id, store_id, entity_id, value)
							VALUES (NULL, :etype, :attr, :sid, :eid, :val) ";

					$binds = array(
						'etype' => $entity_type_id,
						'attr' => $row['attribute_id'],
						'sid' => $store_id,
						'eid' => $entity_id,
						'val' => $value
					);

					$msg = "SQL QUERY = " . $sql;
					Mage::log($msg, $this->log_level, $this->log_filename);

					$write->query($sql, $binds);
				}
			} else { // update
				if (!empty($value) && $value != "") {
					$sql = "UPDATE `" . $productBakTable . "`
							   SET value = :value
							   WHERE value_id = :value_id ";

					$binds = array(
						'value' => $value,
						'value_id' => $chk['value_id']
					);

					$result = $write->query($sql, $binds);
				}
			}

		}
		return "pass";
	}

	/**
	 * special attribute saves for select and multiselect types (frontend_type in Magento)
	 *
	 * @params $row string
	 * @params $entity_id string Magento product id
	 * @params $entity_type_id string Magento product type
	 * @params $attribute_code string Magento product attribute code
	 * @params $value string Magento product attribute value
	 * @params $store_id int Magento Product store id
	 * @params $isCustomAttribute boolean Is the field a custom attribute or core field
	 * @return boolean
	 */
	function save_attribute_select($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id, $isCustomAttribute = null)
	{
		$result = "pass";

		// The switch statment is not really nedded anymore becuase we just need to know
		// if the field is a custom or core field, but keeping it there for backwards compatibility
		// if the $isCustomAttribute is not passed in
		if (null !== $isCustomAttribute && !$isCustomAttribute) {
			$this->save_attribute($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id);
			return $result;
		}

		// Magento hard codes values for some selects
		switch ($attribute_code) {
			case "tax_class_id":
				$this->save_attribute($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id);
				break;
			case "status":
				$this->save_attribute($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id);
				break;
			case "visibility":
				$this->save_attribute($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id);
				break;
			case "enable_googlecheckout":
				$this->save_attribute($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id);
				break;
			default:
				$result = $this->save_attribute_select2($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id);
				break;
		}
		return $result;
	}

	/**
	 * special attribute saves for select and multiselect types (frontend_type in Magento)
	 *
	 * @params $row string
	 * @params $entity_id string Magento product id
	 * @params $entity_type_id string Magento product type
	 * @params $attribute_code string Magento product attribute code
	 * @params $value string Magento product attribute value
	 * @params $store_id int Magento Product store id
	 * @return string pass
	 */
	function save_attribute_select2($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id)
	{
		$eavAttrTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute');
		$read         = Mage::getSingleton('core/resource')->getConnection('core_read');
		$write        = Mage::getSingleton('core/resource')->getConnection('core_write');

		// First get attribute_id
		$sql = "SELECT attribute_id, backend_type FROM " . $eavAttrTable . "
                           WHERE attribute_code LIKE :attribute_code
                           AND entity_type_id = :entity_type_id ";

		$binds  = array(
			'attribute_code' => $attribute_code,
			'entity_type_id' => $entity_type_id
		);
		$result = $read->query($sql, $binds);
		$row1   = $result->fetch(PDO::FETCH_ASSOC);

		$eavAttrOptTable    = Mage::getSingleton('core/resource')->getTableName('eav_attribute_option');
		$eavAttrOptValTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute_option_value');

		$sql = "SELECT EAOV.*, EAO.*
				   FROM " . $eavAttrOptValTable . " as EAOV, " . $eavAttrOptTable . " as EAO
				   WHERE EAO.attribute_id = :attribute_id
				   AND EAOV.option_id = EAO.option_id";

		$binds  = array(
			'attribute_id' => $row1['attribute_id']
		);
		$result = $read->query($sql, $binds);
		$row2   = $result->fetchAll(); // correct, returns all option_values in array

		// LOOK FOR A MATCH, THEN ADD THIS OPTION TO THE PRODUCT
		$match   = false;
		$matchId = null;
		$value   = trim($value);

		foreach ($row2 as $selectOption) {
			if (strcasecmp($selectOption['value'], $value) == 0) {
				$match   = true;
				$matchId = $selectOption['option_id'];
			}
		}

		// if we coulden't find a matching value by checking the label name
		// attempt to find matching option ID
		if (!$match) {
			foreach ($row2 as $selectOption) {
				if ($value == $selectOption['option_id']) {
					$match   = true;
					$matchId = $selectOption['option_id'];
				}
			}
		}

		if ($match) {
			// IF Match
			// catalog_product_index_eav.value - one for each value

			// query for existing entry in catalog_product_index_eav
			$prodIndxEavTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_index_eav');

			$sql   = "SELECT * FROM " . $prodIndxEavTable . "
						WHERE attribute_id = :attribute_id
						AND entity_id = :entity_id
						AND value = :matchId
						AND store_id = :store_id ";
			$binds = array(
				'attribute_id' => $row1['attribute_id'],
				'entity_id'    => $entity_id,
				'matchId'      => $matchId,
				'store_id'     => $store_id
			);

			$result = $read->query($sql, $binds);
			$chk2   = $result->fetch(PDO::FETCH_ASSOC);

			if (empty($chk2)) { // none, existing, insert it
				if (!empty($matchId) && $matchId != "") {
					$sql    = "INSERT INTO " . $prodIndxEavTable . "(entity_id, attribute_id, store_id, value )
								VALUES (:entity_id, :attribute_id, :store_id, :value)";
					$binds  = array(
						'entity_id' => $entity_id,
						'attribute_id' => $row1['attribute_id'],
						'store_id' => $store_id,
						'value' => $matchId
					);
					$result = $write->query($sql, $binds);
				}
			} else {
				if (!empty($matchId) && $matchId != "") {
					$sql    = "UPDATE " . $prodIndxEavTable . "
								SET  value = :value
								WHERE entity_id = :entity_id
								AND   store_id = :store_id
								AND   attribute_id = :attribute_id ";
					$binds  = array(
						'entity_id' => $entity_id,
						'attribute_id' => $row1['attribute_id'],
						'store_id' => $store_id,
						'value' => $matchId
					);
					$result = $write->query($sql, $binds);
				}
			}

			// Insert Select into catalog_product_ent_int
			// query for existing entry in catalog_product_index_eav
			$productIntTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_int');
			$sql             = "SELECT * FROM " . $productIntTable . "
						WHERE attribute_id = :attribute_id
						AND entity_id = :entity_id
						AND store_id = 0 ";

			$binds = array(
				'entity_id' => $entity_id,
				'attribute_id' => $row1['attribute_id']
			);

			$result = $read->query($sql, $binds);
			//    AND value = '" . $matchId . "'");
			$chk3   = $result->fetch(PDO::FETCH_ASSOC);

			if (empty($chk3)) { // none, existing, insert it
				if (!empty($matchId) && $matchId != "") {
					$sql = "INSERT INTO " . $productIntTable . " (value_id, entity_type_id, attribute_id, store_id, entity_id, value)
								VALUES (NULL, :entity_type_id, :attribute_id, 0, :entity_id, :matchId)";

					$binds  = array(
						'entity_type_id' => $entity_type_id,
						'attribute_id' => $row1['attribute_id'],
						'entity_id' => $entity_id,
						'matchId' => $matchId
					);
					$result = $write->query($sql, $binds);
				}
			} else { //update it
				if (!empty($matchId) && $matchId != "") {
					$sql = "UPDATE " . $productIntTable . "
								SET  value = :value
								WHERE value_id = :value_id ";

					$binds  = array(
						'value' => $matchId,
						'value_id' => $chk3['value_id']
					);
					$result = $write->query($sql, $binds);
				}
			}
		} else { // NO MATCH, LOG IT IN MISSING OPTION VALUES LOG
			if (!empty($value) && ($value != " " || $value != "")) {
				$msg = "<h2>This OPTION is not defined in your storefront and may need added manually,
               you need to review your Magento attributes for consistency.</h2><br>";
				$msg .= "<h1>OPTION: " . $attribute_code . "</h1><h1>VALUE = " . $value . "</h1><hr>";
			}
		}
		return "pass";
	}

	/**
	 * special attribute saves for select and multiselect types (frontend_type in Magento)
	 *
	 * @params $row string
	 * @params $entity_id string Magento product id
	 * @params $entity_type_id string Magento product type
	 * @params $attribute_code string Magento product attribute code
	 * @params $value string Magento product attribute value
	 * @params $store_id int Magento Product store id
	 * @return string pass
	 */
	function save_attribute_multiselect($row, $entity_id, $entity_type_id, $attribute_code, $value, $store_id)
	{
		$read  = Mage::getSingleton('core/resource')->getConnection('core_read');
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');

		// First get attribute_id, but
		$eavAttrTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute');

		$sql = "SELECT attribute_id, backend_type FROM " . $eavAttrTable . "
					WHERE attribute_code LIKE :attribute_code
					AND entity_type_id = :entity_type_id ";

		$binds  = array(
			'attribute_code' => $attribute_code,
			'entity_type_id' => $entity_type_id
		);
		$result = $read->query($sql, $binds);
		$row1   = $result->fetch(PDO::FETCH_ASSOC);

		$eavAttrOptTable    = Mage::getSingleton('core/resource')->getTableName('eav_attribute_option');
		$eavAttrOptValTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute_option_value');
		$sql                = "SELECT EAOV.*, EAO.*
					FROM " . $eavAttrOptValTable . " as EAOV, " . $eavAttrOptTable . " as EAO
					WHERE EAO.attribute_id = :attribute_id
					AND EAOV.option_id = EAO.option_id";
		$binds              = array(
			'attribute_id' => $row1['attribute_id']
		);

		$result = $read->query($sql, $binds);
		$row2   = $result->fetchAll(); // correct, returns all option_values in array

		// LOOK FOR A MATCH, THEN ADD THIS OPTION TO THE PRODUCT
		$match   = false;
		$matchId = null;
		foreach ($row2 as $r) {
			if (strcasecmp($r['value'], rtrim(ltrim($value))) == 0) {
				$match   = true;
				$matchId = $r['option_id'];
			}
		}

		$productVarTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_varchar');

		if ($match && $matchId != null) {
			// IF Match
			// catalog_product_entity_varchar.value - one listing with all options
			// catalog_product_index_eav.value - one for each value
			// STORE 0 first
			// query for existing entry in catalog_product_entity_varchar
			$sql    = "SELECT * FROM " . $productVarTable . "
							WHERE attribute_id = :attribute_id
							AND entity_type_id = :entity_type_id
							AND entity_id = :entity_id
							AND store_id = 0";
			$binds  = array(
				'attribute_id' => $row1['attribute_id'],
				'entity_type_id' => $entity_type_id,
				'entity_id' => $entity_id
			);
			$result = $read->query($sql, $binds);
			$chk    = $result->fetch(PDO::FETCH_ASSOC);

			if (empty($chk)) { // none, existing, insert it
				if (!empty($value) && $value != "") {
					echo "doing insert on catalog_product_entity_varchar: " . " attribute_id = " . $row1['attribute_id'] . " entity_id = " . $entity_id . " store_id = 0" . " value/MatchId = " . $matchId . "<br>";

					$sql = "INSERT INTO " . $productVarTable . " (value_id, entity_type_id, attribute_id, store_id, entity_id, value)
                           VALUES (NULL, :entity_type_id, :attribute_id, 0, :entity_id, :value)";

					$binds = array(
						'entity_type_id' => $entity_type_id,
						'attribute_id' => $row1['attribute_id'],
						'entity_id' => $entity_id,
						'value' => $matchId
					);

					$result = $write->query($sql, $binds);
				}
			} else { // explode the value, see if this one is included, update if needed
				$vals = explode(',', $chk['value']);

				$match2 = false;
				foreach ($vals as $val) {
					if (strcasecmp($val, $matchId) == 0) {
						$match2 = true;
					}
				}

				// If Match2 == false; append it
				if ($match2 == false) {
					if (!empty($matchId) && $matchId != "") {
						$new_value = $chk['value'] . "," . $matchId;

						echo "doing UPDATE on catalog_product_entity_varchar with new_value = " . $new_value . "<br>";

						$sql = "UPDATE " . $productVarTable . "
									SET value = :value
									WHERE value_id = :value_id ";

						$binds  = array(
							'value' => $new_value,
							'value_id' => $chk['value_id']
						);
						$result = $write->query($sql, $binds);
					}
				} else {
					echo "found match in catalog_entity_varchar for StoreID: 0, no update needed. <br> ";
				}

			}


			// TARGET STORE NEXT
			// query for existing entry in catalog_product_entity_varchar
			$sql   = "SELECT * FROM " . $productVarTable . "
						WHERE attribute_id = :attribute_id
						AND entity_type_id = :entity_type_id
						AND entity_id = :entity_id
						AND store_id = :store_id ";
			$binds = array(
				'attribute_id' => $row1['attribute_id'],
				'entity_type_id' => $entity_type_id,
				'entity_id' => $entity_id,
				'store_id' => $store_id
			);

			$result = $read->query($sql, $binds);
			$chk    = $result->fetch(PDO::FETCH_ASSOC);

			if (empty($chk)) { // none, existing, insert it
				if (!empty($value) && $value != "") {
					echo "doing insert on catalog_product_entity_varchar: " . " attribute_id = " . $row1['attribute_id'] . " entity_id = " . $entity_id . " store_id = " . $store_id . " value/MatchId = " . $matchId . "<br>";

					$sql    = "INSERT INTO " . $productVarTable . " (value_id, entity_type_id, attribute_id, store_id, entity_id,  value)
                           VALUES (NULL, :entity_type_id, :attribute_id, :store_id, :entity_id, :value)";
					$binds  = array(
						'entity_type_id' => $entity_type_id,
						'attribute_id' => $row1['attribute_id'],
						'store_id' => $store_id,
						'entity_id' => $entity_id,
						'value' => $matchId
					);
					$result = $write->query($sql, $binds);
				}
			} else { // explode the value, see if this one is included, update if needed
				$vals = explode(',', $chk['value']);

				$match2 = false;
				foreach ($vals as $val) {
					if (strcasecmp($val, $matchId) == 0) {
						$match2 = true;
					}
				}

				// If Match2 == false; append it
				if ($match2 == false) {
					if (!empty($matchId) && $matchId != "") {
						$new_value = $chk['value'] . "," . $matchId;

						echo "doing UPDATE on catalog_product_entity_varchar with new_value = " . $new_value . "<br>";

						$sql = "UPDATE " . $productVarTable . "
									SET value = '" . $new_value . "'
									WHERE value_id = :value_id ";

						$binds  = array(
							'value_id' => $chk['value_id']
						);
						$result = $write->query($sql, $binds);
					}

				} else {
					echo "found match in catalog_entity_varchar for StoreID: $store_id, no update needed. <br> ";
				}

			}
			$prodIndxEavTable = Mage::getSingleton('core/resource')->getTableName('catalog_product_index_eav');

			// query for existing entry in catalog_product_index_eav
			$sql = "SELECT * FROM " . $prodIndxEavTable . "
						WHERE attribute_id = :attribute_id
						AND entity_id = :entity_id
						AND value = :value
						AND store_id = :store_id ";

			$binds  = array(
				'attribute_id' => $row1['attribute_id'],
				'entity_id' => $entity_id,
				'value' => $matchId,
				'store_id' => $store_id
			);
			$result = $read->query($sql, $binds);
			$chk2   = $result->fetch(PDO::FETCH_ASSOC);

			if (empty($chk2)) { // none, existing, insert it
				if (!empty($matchId) && $matchId != "") {
					echo "doing insert on catalog_product_index_eav,
                      entity_id = $entity_id, attribute = " . $row1['attribute_id'] . " store_id = $store_id , matchid = $matchId<br>";

					$sql    = "INSERT INTO " . $prodIndxEavTable . "(entity_id, attribute_id, store_id, value)
                           VALUES(:entity_id, :attribute_id, :store_id, :value)";
					$binds  = array(
						'entity_id' => $entity_id,
						'attribute_id' => $row1['attribute_id'],
						'store_id' => $store_id,
						'value' => $matchId
					);
					$result = $write->query($sql, $binds);
					echo "complete catalog_product_index_eav insert<br>";
				}
			} else { // exist, update only
				if (!empty($matchId) && $matchId != "") {
					echo "doing UDPATE on catalog_product_index_eav,
                      entity_id = $entity_id, attribute = " . $row1['attribute_id'] . " store_id = $store_id , matchid = $matchId<br>";
					$sql = "UPDATE " . $prodIndxEavTable . "
								SET  value = :value
								WHERE entity_id = :entity_id
								AND   store_id = :store_id
								AND   attribute_id = :attribute_id ";

					$binds  = array(
						'value' => $matchId,
						'entity_id' => $entity_id,
						'store_id' => $store_id,
						'attribute_id' => $row1['attribute_id']
					);
					$result = $write->query($sql, $binds);
					echo "complete catalog_product_index_eav<br>";
				}
			}
		} else { // NO MATCH, LOG IT IN MISSING OPTION VALUES LOG
			if (!empty($value) && ($value != " " || $value != "")) {
				$msg = "<h2>This OPTION is not defined in your storefront and may need added manually,
               you need to review your Magento attributes for consistency.</h2><br>";
				$msg .= "<h1>OPTION: " . $attribute_code . "</h1><h1> VALUE = " . $value . "</h1><hr>";
				echo $msg;
				Mage::log($msg, $this->log_level, "ERROR_saleswarp_product_api-missing_options.log");
			}
		}
		return "pass";
	}

	/**
	 * update product name
	 *
	 * @param $store_prod_id int product id
	 * @param $store_id int store id
	 * @param $value string attribute value
	 * @param $code string attribute code
	 * @param $entity_type_id int entity type id
	 * @return
	 */
	function update_name($store_prod_id, $store_id, $value, $code = "name", $entity_type_id = 4)
	{
		$eavAttrTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute');

		if ($this->enable_logs) {
			$msg = "Updating Name: attribute_code = " . $code . ", value = " . $value . ", entity_type_id = " . $entity_type_id;
			Mage::log($msg, $this->log_level, $this->log_filename);
		}

		$w        = Mage::getSingleton('core/resource')->getConnection('core_write');
		$datetime = strftime('%Y-%m-%d %H:%M:%S', time());

		$result = $w->query("SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
                        WHERE `attribute_code` LIKE '" . $code . "'
                        AND entity_type_id = '" . $entity_type_id . "'");
		$row    = $result->fetch(PDO::FETCH_ASSOC);

		$result = $this->save_attribute($row, $store_prod_id, $entity_type_id, $code, $value, $store_id);
		if ($result != "pass") {
			return "fail";
		}
		return $result;
	}

	/**
	 * update product name
	 *
	 * @param $store_prod_id int product id
	 * @param $store_id int store id
	 * @param $value string attribute value
	 * @param $code string attribute code
	 * @param $entity_type_id int entity type id
	 * @return
	 */
	function update_description($store_prod_id, $store_id, $value, $code = "description", $entity_type_id = 4)
	{
		$eavAttrTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute');

		$read     = Mage::getSingleton('core/resource')->getConnection('core_read');

		$sql = "SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
					WHERE `attribute_code` LIKE :attribute_code
					AND entity_type_id = :entity_type_id ";

		$binds  = array(
			'attribute_code' => $attribute_code,
			'entity_type_id' => $entity_type_id
		);
		$result = $read->query($sql, $binds);
		$row    = $result->fetch(PDO::FETCH_ASSOC);


		$result = $this->save_attribute($row, $store_prod_id, $entity_type_id, $code, $value, $store_id);
		if ($result != "pass") {
			return "fail";
		}
		return $result;
	}

	/**
	 * update product name
	 *
	 * @param $store_prod_id int product id
	 * @param $store_id int store id
	 * @param $value string attribute value
	 * @param $code string attribute code
	 * @param $entity_type_id int entity type id
	 * @return
	 */
	function update_short_description($store_prod_id, $store_id, $value, $code = "short_description", $entity_type_id = 4)
	{
		$eavAttrTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute');

		$read     = Mage::getSingleton('core/resource')->getConnection('core_read');

		$sql = "SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
					WHERE `attribute_code` LIKE :attribute_code
					AND entity_type_id = :entity_type_id ";

		$binds  = array(
			'attribute_code' => $attribute_code,
			'entity_type_id' => $entity_type_id
		);
		$result = $read->query($sql, $binds);

		$row = $result->fetch(PDO::FETCH_ASSOC);


		$result = $this->save_attribute($row, $store_prod_id, $entity_type_id, $code, $value, $store_id);
		if ($result != "pass") {
			return "fail";
		}
		return $result;
	}

	/**
	 * update product price
	 *
	 * @param $store_prod_id int product id
	 * @param $store_id int store id
	 * @param $msrp float market selling price
	 * @param $saleprice float sale price
	 * @param $salefrom date sale from date
	 * @param $saleto date sale to date
	 * @param $entity_type_id int entity type id
	 * @return
	 */
	function update_prices($store_prod_id, $store_id, $msrp, $saleprice, $salefrom = null, $saleto = null, $entity_type_id = 4)
	{
		$eavAttrTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute');
		$read         = Mage::getSingleton('core/resource')->getConnection('core_read');
		$datetime     = strftime('%Y-%m-%d %H:%M:%S', time());

		// Price first
		$code = "price";
		$sql  = "SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
					WHERE `attribute_code` LIKE :attribute_code
					AND entity_type_id = :entity_type_id ";

		$binds  = array(
			'attribute_code' => $code,
			'entity_type_id' => $entity_type_id
		);
		$result = $read->query($sql, $binds);
		$row    = $result->fetch(PDO::FETCH_ASSOC);

		$result = $this->save_attribute($row, $store_prod_id, $entity_type_id, $code, $msrp, $store_id);

		if ($result != "pass") {
			return "fail";
		}
		// SalePrice
		$code = "special_price";
		$sql  = "SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
					WHERE `attribute_code` LIKE :attribute_code
					AND entity_type_id = :entity_type_id ";

		$binds  = array(
			'attribute_code' => $code,
			'entity_type_id' => $entity_type_id
		);
		$result = $read->query($sql, $binds);
		$row    = $result->fetch(PDO::FETCH_ASSOC);

		$result = $this->save_attribute($row, $store_prod_id, $entity_type_id, $code, $saleprice, $store_id);
		if ($result != "pass") {
			return "fail";
		}

		if ($salefrom != null) { // special_from_date
			$code = "special_from_date";
			$sql  = "SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
					WHERE `attribute_code` LIKE :attribute_code
					AND entity_type_id = :entity_type_id ";

			$binds  = array(
				'attribute_code' => $code,
				'entity_type_id' => $entity_type_id
			);
			$result = $read->query($sql, $binds);
			$row    = $result->fetch(PDO::FETCH_ASSOC);

			$result = $this->save_attribute($row, $store_prod_id, $entity_type_id, $code, $salefrom, $store_id);
			if ($result != "pass") {
				return "fail";
			}
		}

		if ($saleto != null) {
			// special_from_date
			$code = "special_to_date";
			$sql  = "SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
					WHERE `attribute_code` LIKE :attribute_code
					AND entity_type_id = :entity_type_id ";

			$binds  = array(
				'attribute_code' => $code,
				'entity_type_id' => $entity_type_id
			);
			$result = $read->query($sql, $binds);
			$row    = $result->fetch(PDO::FETCH_ASSOC);

			$result = $this->save_attribute($row, $store_prod_id, $entity_type_id, $code, $saleto, $store_id);
			if ($result != "pass") {
				return "fail";
			}
		}

		return $result;
	}

	/**
	 * update codes
	 *
	 * @param $store_prod_id int product id
	 * @param $store_id int store id
	 * @param $codes array attribute and value
	 * @param $entity_type_id int entity type id
	 * @return
	 */
	function update_codes($store_prod_id, $store_id, $codes, $entity_type_id = 4)
	{
		$eavAttrTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute');

		$pass      = "pass";
		$entity_id = $store_prod_id;

		$read     = Mage::getSingleton('core/resource')->getConnection('core_read');

		foreach ($codes as $code => $value) {

			$sql = "SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
					WHERE `attribute_code` LIKE :attribute_code
					AND entity_type_id = :entity_type_id ";

			$binds  = array(
				'attribute_code' => $code,
				'entity_type_id' => $entity_type_id
			);
			$result = $read->query($sql, $binds);
			$row    = $result->fetch(PDO::FETCH_ASSOC);

			$result = $this->save_attribute($row, $entity_id, $entity_type_id, $code, $value, $store_id);
			if ($result != "pass") {
				return "fail";
			}

		}
		return $pass;

	}


	/**
	 * update attributes
	 *
	 * @param $store_prod_id int product id
	 * @param $store_id int store id
	 * @param $attributes array attribute and value
	 * @param $entity_type_id int entity type id
	 * @return
	 */
	function update_attributes($store_prod_id, $store_id, $attributes, $entity_type_id = 4)
	{
		$eavAttrTable = Mage::getSingleton('core/resource')->getTableName('eav_attribute');


		$result    = "pass";
		$entity_id = $store_prod_id;

		$read     = Mage::getSingleton('core/resource')->getConnection('core_read');
		$datetime = strftime('%Y-%m-%d %H:%M:%S', time());

		foreach ($attributes as $code => $value) {

			switch ($code) { // Add special groups here
				case "age_groups":
					foreach ($value as $v) {
						if (!empty($v) && $v != '') {
							$code = "age_group";

							$sql = "SELECT attribute_id, backend_type, frontend_input FROM `" . $eavAttrTable . "`
									WHERE `attribute_code` LIKE :attribute_code
									AND entity_type_id = :entity_type_id ";

							$binds  = array(
								'attribute_code' => $code,
								'entity_type_id' => $entity_type_id
							);
							$result = $read->query($sql, $binds);
							$row    = $result->fetch(PDO::FETCH_ASSOC);
							if ($this->enable_logs) {
								$msg = "calling save_attribute_multiselect for Age Groups, Code : " . $code . "; Value: " . $v;
								Mage::log($msg, $this->log_level, "saleswarp_update_products");
							}

							$result = $this->save_attribute_multiselect($row, $entity_id, $entity_type_id, $code, $v, $store_id);
							if ($result != "pass") {
								return "fail";
							}
						}
					}
					break;
				default:
					if (!empty($value) && $value != '') {

						$sql = "SELECT attribute_id, backend_type, frontend_input, is_user_defined FROM `" . $eavAttrTable . "`
									WHERE `attribute_code` LIKE :attribute_code
									AND entity_type_id = :entity_type_id ";

						$binds  = array(
							'attribute_code' => $code,
							'entity_type_id' => $entity_type_id
						);
						$result = $read->query($sql, $binds);
						$row    = $result->fetch(PDO::FETCH_ASSOC);

						if ($row['frontend_input'] == 'select') {

							$result = $this->save_attribute_select($row, $entity_id, $entity_type_id, $code, $value, $store_id, $row['is_user_defined']);
							if ($result != "pass") {
								return "fail";
							}

						} elseif ($row['frontend_input'] == 'multiselect') {

							$result = $this->save_attribute_multiselect($row, $entity_id, $entity_type_id, $code, $value, $store_id);
							if ($result != "pass") {
								return "fail";
							}

						} else {
							$result = $this->save_attribute($row, $entity_id, $entity_type_id, $code, $value, $store_id);
							if ($result != "pass") {
								return "fail";
							}
						}
					}
					break;
			}
		}
		return $result;
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
	 */
	function update_inventory($store_prod_id, $store_qty, $discontinued, $attribute_id, $status = 1, $website_id = 1, $stock_id = 1)
	{
		$invStockItemTable   = Mage::getSingleton('core/resource')->getTableName('cataloginventory_stock_item');
		$invStockStatusTable = Mage::getSingleton('core/resource')->getTableName('cataloginventory_stock_status');
		$prodDateTimeTable   = Mage::getSingleton('core/resource')->getTableName('catalog_product_entity_datetime');

		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$read  = Mage::getSingleton('core/resource')->getConnection('core_read');

		if ($store_qty <= 0 || $discontinued == 1) {
			$status = 0;
		} else {
			$status = 1;
		}

		if (is_null($store_qty)) {
			$store_qty = 0;
			$status    = 0;
		}

		$sql    = "UPDATE " . $invStockItemTable . "
					SET	qty =  :qty,
					is_in_stock = :is_in_stock
					WHERE  product_id = :product_id ";
		$binds  = array(
			'qty' => $store_qty,
			'is_in_stock' => $status,
			'product_id' => $store_prod_id
		);
		$result = $write->query($sql, $binds);

		$sql   = "SELECT * FROM " . $invStockStatusTable . "
					WHERE  product_id	= :product_id
					AND    website_id 	= :website_id
					AND    stock_id 	= :stock_id ";
		$binds = array(
			'product_id' => $store_prod_id,
			'website_id' => $website_id,
			'stock_id' => $stock_id
		);
		$chk   = $read->fetchRow($sql, $binds);
		if (empty($chk)) {
			$sql = "INSERT INTO " . $invStockStatusTable . "
							SET qty = :qty,
							stock_status = :stock_status,
							stock_id = :stock_id,
							website_id = :website_id,
							product_id = :product_id ";

			$binds   = array(
				'qty' => $store_qty,
				'stock_status' => $status,
				'stock_id' => $stock_id,
				'website_id' => $website_id,
				'product_id' => $store_prod_id
			);
			$result2 = $write->query($sql, $binds);
		} else { // do update
			$sql     = "UPDATE " . $invStockStatusTable . "
							SET	qty = :qty, stock_status = :stock_status
							WHERE  product_id = :product_id ";
			$binds   = array(
				'qty' => $store_qty,
				'stock_status' => $status,
				'product_id' => $store_prod_id
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
			$sql           = "INSERT INTO " . $prodDateTimeTable . "
						SET value = NOW(),
						entity_type_id = 4,
						store_id = 0,
						entity_id = $store_prod_id,
						attribute_id = $attribute_id";
			$insert_result = $write->query($sql);
		} else { // update it
			$sql           = "UPDATE " . $prodDateTimeTable . "
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

	/**
	 * get default store root category id
	 */
	public function get_default_store_root_cat_id()
	{
		$coreStorGroupTable = Mage::getSingleton('core/resource')->getTableName('core_store_group');
		$coreWebTable       = Mage::getSingleton('core/resource')->getTableName('core_website');

		$sql   = "SELECT s.root_category_id FROM " . $coreStorGroupTable . " s
					LEFT JOIN " . $coreWebTable . " w ON s.website_id=w.website_id
					WHERE w.is_default=1";
		$prods = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchRow($sql);
		return $prods['root_category_id'];
	}

	/**
	 * superadmin feature, don't keep on in production or you will regret it...
	 */
	function super_scary_fast_magento_delete_all_products()
	{
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$cnt   = 0;
		$write->query('SET FOREIGN_KEY_CHECKS = 0');
		foreach ($this->product_truncate_all_tables as $t1) {
			$tmpTable = Mage::getSingleton('core/resource')->getTableName($t1);
			// truncate this table
			echo "truncating table : " . $t1 . "<br>";
			$result2 = $write->query('TRUNCATE ' . $tmpTable . ';');
			$cnt++;
		}
		$w1->query('SET FOREIGN_KEY_CHECKS = 1');
		return "products have been cleared, # tables cleared = " . $cnt;
	}
}