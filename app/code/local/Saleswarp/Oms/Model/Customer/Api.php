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

/**
 * Use this API to get/set key config data for the storefronts
 */
class Saleswarp_Oms_Model_Customer_Api extends Mage_Customer_Model_Customer_Api
{
	/** 
	* Get Customer Group name
	* @params group id 
	* @return Group name
	*/
	function get_customer_group_name_by_id($groupId)
	{
		$group = Mage::getModel('customer/group')->load($groupId);
		if ($group->getId()) {
			return $group->getCode();
		} else {
			return "Guest";
		}
	}
}
?>