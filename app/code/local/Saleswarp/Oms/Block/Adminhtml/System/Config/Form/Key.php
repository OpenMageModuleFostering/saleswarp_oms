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

class Saleswarp_Oms_Block_Adminhtml_System_Config_Form_Key extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	/**
	* Return element html
	* 
	* @param  Varien_Data_Form_Element_Abstract $element
	* @return string
	*/
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
	{
		$element->setDisabled('disabled');
		return parent::_getElementHtml($element);
	}
}