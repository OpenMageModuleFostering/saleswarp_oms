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

class Saleswarp_Oms_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	/*
	* Set template
	*/
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('oms/system/config/button.phtml');
	}

	/**
	* Return element html
	* 
	* @param  Varien_Data_Form_Element_Abstract $element
	* @return string
	*/
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		return $this->_toHtml();
	}
	
	/**
	* Return ajax url for button
	* 
	* @return string
	*/
	public function getAjaxCheckUrl()
	{
		return Mage::helper('adminhtml')->getUrl('admin_oms/adminhtml_getkey');
	}
	
	/**
	* Generate button html
	* 
	* @return string
	*/
	public function getButtonHtml()
	{
		$button = $this->getLayout()->createBlock('adminhtml/widget_button')
			->setData(array(
				'id'        => 'saleswarp_oms_button',
				'label'     => $this->helper('adminhtml')->__('Get Key'),
				'onclick'   => 'javascript:getKey(); return false;'
				));
		return $button->toHtml();
	}

	public function isKeyAvailable() {
		$key	= Mage::getStoreConfig('oms/registration/key');
		if($key && trim($key)) {
			return true;
		} else {
			return false;
		}
	}

	public function getKeyData() {
		$data			= array();
		$data['key']	= Mage::getStoreConfig('oms/registration/key');
		$coreVariable	= Mage::getModel('core/variable');
		$data['create_url']	= $coreVariable->loadByCode('saleswarp_create_url')->getValue('plain');
		$data['login_url']	= $coreVariable->loadByCode('saleswarp_login_url')->getValue('plain');
		return $data;
	}
}