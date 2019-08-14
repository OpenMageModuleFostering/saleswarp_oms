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
class Saleswarp_Oms_Adminhtml_GetkeyController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$return	= array();
		$value	= $this->makeKeyRequest();
		if($value) {
			$key = new Mage_Core_Model_Config();
			$key->saveConfig('oms/registration/key', $value);
			
			$return['success']	= 1;
			$return['key']		= $value;
		} else {
			$return['success']	= 0;
		}
		$return	= Mage::helper('core')->jsonEncode($return);
		$this->getResponse()->setHeader('Content-type', 'application/json');
		$this->getResponse()->setBody($return);
	}
	
	public function makeKeyRequest() {
		
		$data	= array();
		$data['BaseUrl']	= Mage::getBaseUrl();
		
		$admin = Mage::getSingleton('admin/session')->getUser()->getData();
		$data['admin']['username']	= $admin['username'];
		$data['admin']['email']		= $admin['email'];
		
		$url = Mage::getModel('core/variable')->loadByCode('saleswarp_api_url')->getValue('plain');

		$dataString = http_build_query($data); 
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 6000);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		$return 	= curl_exec($ch);

		$info		= curl_getinfo($ch);
		if($info['http_code'] == '200') {
			return $return;
		} else {
			return false;
		}
	}
}