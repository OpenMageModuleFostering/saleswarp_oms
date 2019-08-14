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
class Saleswarp_Oms_Model_Saleswarp extends Mage_Payment_Model_Method_Abstract
{
	protected $_code 			= 'saleswarp';
	protected $_formBlockType 	= 'oms/form_saleswarp';
	protected $_infoBlockType 	= 'oms/info_saleswarp';
	protected $_canUseInternal	= true;
    protected $_canUseCheckout	= false;

	public function assignData($data)
	{
		if (!($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}
		$info = $this->getInfoInstance();
		$info->setTransctionNo($data->getTransctionNo())
		->setPaymentMethod($data->getPaymentMethod());
		return $this;
	}

	public function validate()
	{
		parent::validate();

		$info = $this->getInfoInstance();

		$no = $info->getTransctionNo();
		$method = $info->getPaymentMethod();
		if(empty($no) || empty($method)){
			$errorCode = 'invalid_data';
			$errorMsg = $this->_getHelper()->__('Transaction No and Payment Method are required fields');
		}

		if($errorMsg){
			Mage::throwException($errorMsg);
		}

		return $this;
	}
}
?>
