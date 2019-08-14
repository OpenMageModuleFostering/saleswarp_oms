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
class Saleswarp_Oms_FastapiController extends Mage_Core_Controller_Front_Action {

	public $authError = '';
	
	/** 
	* * Receive API call and process API
	* *
	* * @param json encoded data
	* * @return json encode data
	* */
	public function IndexAction() 
	{
		$response = array();
		$data = json_decode(@file_get_contents('php://input'), true);
		
		if (empty($data)) {
			$this->_redirect('/');
			return;
		}

		if (isset($data['hash']['key'])) {
			// authenticate
			if(!$this->ApiAuthentication($data['hash']['key'])) {
				$response['error_msg'] = $this->authError;
				$response['error_code'] = '2';
				echo json_encode($response);
				exit;
			}
			// call method api
			$response['data'] = $this->callFastApi($data);
			if(!$response['data']) {
				$response['error_msg'] = 'Invalid data';
				$response['error_code'] = '3';
			} else {
				$response['error_msg'] = '';
				$response['error_code'] = '0';
			}
		} else {
			$response['error_msg'] = 'Invalid data';
			$response['error_code'] = '1';
		}
		echo json_encode($response);
		exit;
	}
	
	/** 
	* * Load fast api and call methods
	* *
	* * @param array of data
	* * @return array of data
	* */
	public function callFastApi($data = array()) 
	{
		if (empty($data)) {
			return false;
		} else {
			if (isset($data['api']) && strstr($data['api'], 'saleswarp_fastapi') !== false) {
				// load api
				$className 	= $data['api'];
				
				switch ($className) {
					case 'saleswarp_fastapi_model_order_api':
						$className	= 'oms/order_api';
						break;
					case 'saleswarp_fastapi_model_product_api':
						$className	= 'oms/product_api';
						break;
					case 'saleswarp_fastapi_model_category_api':
						$className	= 'oms/category_api';
						break;
					case 'saleswarp_fastapi_model_config_api':
						$className	= 'oms/config_api';
						break;
					case 'saleswarp_fastapi_model_product_attribute_media_api':
						$className	= 'oms/product_attribute_media_api';
						break;
					case 'saleswarp_fastapi_model_customer_api':
						$className	= 'oms/customer_api';
						break;
				}

				try {
					$mage		= Mage::getModel($className);
					if(!method_exists($mage, $data['methodName'])) {
						throw new Exception('Class or Function does not exists'); 
					} else {
						$fastData 	= call_user_func_array(array($mage, $data['methodName']), $data['methodParams']);
					}
				} catch(Exception $e) {
					return [$e->getMessage()];
				}
				
				return $fastData;
			} else {
				return false;
			}
		}
	}

	/** 
	* * Authentication API request
	* *
	* * @params string hash key
	* * @return bool(trur or false)
	* */
	public function ApiAuthentication($hash = null)
	{
		if(empty($hash)) {
			$this->authError = 'No hash key supplied';
			return false;
		} else {
			$mageHash 		= Mage::getStoreConfig('oms/registration/key');
			
			// get user ip address
			$remoteIP 		= Mage::helper('core/http')->getRemoteAddr(false);
			if (!empty($mageHash) && $hash == $mageHash) {
				return true;
			} else {
				$this->authError = 'Incorrect Hash key';
				return false;
			}
		}
	}
}