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
class Saleswarp_Oms_Model_Order_Api extends Mage_Sales_Model_Order_Api
{
	const STATE_RETURN_REQUESTED 	= 'return_requested';
	const STATE_EXCHANGE 			= 'exchange';
	const STATE_ADVANCED_EXCHANGE 	= 'advanced_exchange';
	const STATE_RETURN_RECEIVED 	= 'return_received';
	
	/**
	* return a recent order list
	* if any parameter is set to zero then that will not be considered for order pull.
	*/
	public function get_recent_order_list($limit = 0, $lastdays = 5, $offset = 0)
	{
		$storeId    = Mage::app()->getStore()->getStoreId();
		$conditions = ' WHERE store_id = ' . $storeId . ' AND ';

		// return a list of orders in the last X days
		if ($lastdays != 0) {
			$conditions .= " created_at > '" . strftime('%Y-%m-%d %H:%M:%S', (time() - (24 * 60 * 60 * $lastdays))) . "'";
		}

		// set limit for orders
		if ($limit != 0) {
			$conditions .= " LIMIT $offset, $limit";
		}

		$salesFlatTable = Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
		$sql            = "select * from " . $salesFlatTable . $conditions;

		$orders = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql);
		return $orders;
	}

	/**
	* get active shipping methods 
	*/
	public function getActShipMethods()
	{
		$methods  = Mage::getSingleton('shipping/config')->getActiveCarriers();
		$shipping = array();
		foreach ($methods as $_ccode => $_carrier) {
			if ($_methods = $_carrier->getAllowedMethods()) {
				if (!$_title = Mage::getStoreConfig("carriers/$_ccode/title")) {
					$_title = $_ccode;
				}
				foreach ($_methods as $_mcode => $_method) {
					$_code            = $_ccode . '_' . $_mcode;
					$shipping[$_code] = array(
						'title' => $_method,
						'carrier' => $_title
						);
				}
			}
		}
		return $shipping;
	}

	/**
	* get active Payment methods 
	* */
	function getActPayment()
	{
		$payments = Mage::getSingleton('payment/config')->getActiveMethods();
		foreach ($payments as $paymentCode => $paymentModel) {
			$paymentTitle[] = $paymentCode;
		}
		return $paymentTitle;
	}
	
	/**
	* function to get lineitem id
	* @param magento order id
	* @return lineitems id
	*/
	function getLineItem($mageOrderId)
	{
		// get the order id
		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
		$salesFlatTable = Mage::getSingleton( 'core/resource' )->getTableName( 'sales_flat_order' );
		$orderItemTable = Mage::getSingleton( 'core/resource' )->getTableName( 'sales_flat_order_item' );

		//check for existig group
		$chk = $write->query("SELECT l.item_id, l.product_id FROM ". $salesFlatTable ." ord  INNER JOIN
		". $orderItemTable ." l ON ord.entity_id = l.order_id WHERE ord.increment_id = '".(int)$mageOrderId."'");
		$row = $chk->fetchAll(PDO::FETCH_ASSOC);
		return $row;
	}

	/**
	* Create order in magento 
	*/
	function create_order($data)
	{
		$logFileName = 'create_order.log';
		$customer    = Mage::getModel('customer/customer');
		$quote       = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore('default')->getId());
		
		$password = Mage::getModel('customer/customer')->generatePassword();
		if (!empty($data['BaseCustomer']['email'])) {
			$email = $data['BaseCustomer']['email'];
		} else {
			$email = 'customer_' . $data['BaseCustomer']['id'] . '@saleswarp.com';
		}
	
		if (!empty($data['ShippingAddress']['street'])) {
			$sStreet = $data['ShippingAddress']['street'];
		} else {
			$sStreet = '';
		}
		
		if (!empty($data['ShippingAddress']['street1'])) {
			$sStreet1 = $data['ShippingAddress']['street1'];
		} else {
			$sStreet1 = '';
		}
		
		if (!empty($data['BillingAddress']['street'])) {
			$bStreet = $data['BillingAddress']['street'];
		} else {
			$bStreet = '';
		}
		if (!empty($data['BillingAddress']['street1'])) {
			$bStreet1 = $data['BillingAddress']['street1'];
		} else {
			$bStreet1 = '';
		}
		
		if (!empty($data['ShippingAddress']['region_code']) && $data['ShippingAddress']['region_code'] != 'na') {
			$sregion_id = $data['ShippingAddress']['region_code'];
		} else {
			$sregion_id = '';
		}
		
		if (!empty($data['BillingAddress']['region_code']) && $data['BillingAddress']['region_code'] != 'na') {
			$bregion_id = $data['BillingAddress']['region_code'];
		} else {
			$bregion_id = '';
		}
		
		// customer  address//
		if (!empty($data['BaseCustomer']['street'])) {
			$cStreet = $data['BaseCustomer']['street'];
		} else {
			$cStreet = '';
		}
		if (!empty($data['BaseCustomer']['street1'])) {
			$cStreet1 = $data['BaseCustomer']['street1'];
		} else {
			$cStreet1 = '';
		}
		
		if (!empty($data['BaseCustomer']['region_code']) && $data['BaseCustomer']['region_code'] != 'na') {
			$cregion_id = $data['BaseCustomer']['region_code'];
		} else {
			$cregion_id = '';
		}
		
		$shipAddressData = array(
			'firstname' => (!empty($data['ShippingAddress']['first_name'])) ? $data['ShippingAddress']['first_name'] : '',
			'lastname' => (!empty($data['ShippingAddress']['last_name'])) ? $data['ShippingAddress']['last_name'] : '',
			'street' => array(
				'0' => $sStreet,
				'1' => $sStreet1
				),
			'city' => (!empty($data['ShippingAddress']['city'])) ? $data['ShippingAddress']['city'] : '',
			'postcode' => (!empty($data['ShippingAddress']['post_code'])) ? $data['ShippingAddress']['post_code'] : '',
			'telephone' => ($data['ShippingAddress']['phone']) ? $data['ShippingAddress']['phone'] : '111-111-1111',
			'country_id' => (!empty($data['ShippingAddress']['country_code'])) ? $data['ShippingAddress']['country_code'] : 'US',
			'region_id' => $sregion_id,
			'region' => (!empty($data['ShippingAddress']['state'])) ? $data['ShippingAddress']['state'] : ''
			);

		$billAddressData = array(
			'firstname' => (!empty($data['BillingAddress']['first_name'])) ? $data['BillingAddress']['first_name'] : '',
			'lastname' => (!empty($data['BillingAddress']['last_name'])) ? $data['BillingAddress']['last_name'] : '',
			'street' => array(
				'0' => $bStreet,
				'1' => $bStreet1
			),
			'city' => (!empty($data['BillingAddress']['city'])) ? $data['BillingAddress']['city'] : '',
			'postcode' => (!empty($data['BillingAddress']['post_code'])) ? $data['BillingAddress']['post_code'] : '',
			'telephone' => ($data['BillingAddress']['phone']) ? $data['BillingAddress']['phone'] : '111-111-1111',
			'country_id' => (!empty($data['BillingAddress']['country_code'])) ? $data['BillingAddress']['country_code'] : 'US',
			'region_id' => $bregion_id,
			'region' => (!empty($data['BillingAddress']['state'])) ? $data['BillingAddress']['state'] : ''
		);
		
		$customerAddressData = array(
			'firstname' => (!empty($data['BaseCustomer']['first_name'])) ? $data['BaseCustomer']['first_name'] : '',
			'lastname' => (!empty($data['BaseCustomer']['last_name'])) ? $data['BaseCustomer']['last_name'] : '',
			'street' => array(
				'0' => $cStreet,
				'1' => $cStreet1
			),
			'city' => (!empty($data['BaseCustomer']['city'])) ? $data['BaseCustomer']['city'] : '',
			'postcode' => (!empty($data['BaseCustomer']['post_code'])) ? $data['BaseCustomer']['post_code'] : '',
			'telephone' => ($data['BaseCustomer']['phone']) ? $data['BaseCustomer']['phone'] : '111-111-1111',
			'country_id' => (!empty($data['BaseCustomer']['country_code'])) ? $data['BaseCustomer']['country_code'] : 'US',
			'region_id' => $bregion_id,
			'region' => (!empty($data['BaseCustomer']['state'])) ? $data['BaseCustomer']['state'] : ''
		);
		
		$customer_group_id = $this->get_customer_group_id_by_name($data['BaseCustomer']['group']);
		$customer->setWebsiteId(1);
		$customer->loadByEmail($email);
		
		if (!$customer->getId()) {
			$customer->setEmail($email);
			$customer->setWebsiteId(1);
			$customer->setFirstname($data['BaseCustomer']['first_name']);
			$customer->setLastname($data['BaseCustomer']['last_name']);
			$customer->setPassword($password);
			$customer->setGroupId($customer_group_id);
			try {
				$customer->save();
				$customer->setConfirmation(null);
				$customer->save();
				// save billing address//
				$address = Mage::getModel("customer/address");
				$address->setData($billAddressData)->setCustomerId($customer->getId())->setIsDefaultBilling('1')->setIsDefaultShipping('1')->setSaveInAddressBook('1');
				
				try {
					$address->save();
				}catch (Exception $ex) {
					Mage::log($ex->getMessage(), null, $logFileName);
					return false;
				}
				
				//Make a "login" of new customer
				Mage::getSingleton('customer/session')->loginById($customer->getId());
				$quote->assignCustomer($customer);
			}
			catch (Exception $ex) {
				Mage::log($ex->getMessage(), null, $logFileName);
				return false;
			}
		} else {
			$quote->assignCustomer($customer);
		}
		
		$store_id = $data['BaseOrder']['base_store_id'];
		$lineitem = $data['Lineitem'];
		foreach ($lineitem as $item) {
			// check whether item/product published or not
			$prod_id          = $item['base_product_id'];
			$mage_prod_id     = $item['mageProdId'];
			$prodId[$prod_id] = $item['mageProdId'];
			$qty_ordered      = (int) $item['qty_ordered'];
			$buyInfo          = array('qty' => $qty_ordered);
			$prod             = Mage::getModel('catalog/product')->load($mage_prod_id);
			$prod->setPrice($item['price_after_discount']);
			$prod->setSpecialPrice($item['price_after_discount']);
			try {
				$quote->addProduct($prod, new Varien_Object($buyInfo));
			}
			catch (Exception $e) {
				Mage::log($e->getMessage(), null, $logFileName);
				continue;
			}
		}
		
		$shipMethod = !empty($data['Saleswarp']['freightID']) ? $data['Saleswarp']['freightID'] : 'flatrate_flatrate';
		
		try {
			$billingAddress = $quote->getBillingAddress()->addData($billAddressData);
		}
		catch (Exception $e) {
			Mage::log($e->getMessage(), null, $logFileName);
		}
		$shippingAddress = $quote->getShippingAddress()->addData($shipAddressData);
		$shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod($shipMethod);
		
		$paymentMethod = array(
			'method' => 'saleswarp',
			'transction_no' => $data['Saleswarp']['transactionId'],
			'payment_method' => $data['Saleswarp']['payMethodName']
			);
		
		try {
			$quote->getPayment()->importData($paymentMethod);
		}
		catch (Exception $e) {
			Mage::log($e->getMessage(), null, $logFileName);
			return false;
		}
		$quote->collectTotals()->save();
		
		$service = Mage::getModel('sales/service_quote', $quote);
		
		try {
			$service->submitAll();
		}
		catch (Exception $e) {
			Mage::log($e->getMessage(), null, $logFileName);
			return false;
		}
		
		try {
			$order = $service->getOrder();
			if ($order) {
				$morder_id = $order->getIncrementId();
				Mage::log("Created order: " . $morder_id, null, $logFileName);
				return $morder_id;
			}
		}
		catch (Exception $e) {
			Mage::log($e->getMessage(), null, $logFileName);
			return false;
		}
	}
	
/**
* Get Customer Group id by group name
*/
	function get_customer_group_id_by_name($name)
	{
		$write            = Mage::getSingleton('core/resource')->getConnection('core_write');
		$type             = addslashes($name);
		
		//check for existig group
		$customerGrpTable = Mage::getSingleton('core/resource')->getTableName('customer_group');
		
		$chk = $write->query("SELECT customer_group_id FROM " . $customerGrpTable . " WHERE customer_group_code = '" . $type . "'");
		$row = $chk->fetch(PDO::FETCH_ASSOC);
		if ($row) {
			return $row['customer_group_id'];
		}
		return false;
	}
	
	/**
	* * Cancel Order 
	*/
	function cancel_order($magento_orderId)
	{
		$orderModel = Mage::getModel('sales/order')->loadByIncrementId($magento_orderId);
		if ($orderModel->canCancel()) {
			$orderModel->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
			return true;
		} else if($this->creditmemo_order($magento_orderId)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	* Hold order 
	*/
	function hold_order($magento_orderId)
	{
		$orderModel = Mage::getModel('sales/order')->loadByIncrementId($magento_orderId);
		if ($orderModel->canHold()) {
			$orderModel->setState(Mage_Sales_Model_Order::STATE_HOLDED, true)->save();
			return true;
		} else {
			return false;
		}
	}

	/**
	* Create Credit memo
	*/
	function creditmemo_order($magento_orderId)
	{
		Mage::getModel('sales/order_creditmemo_api')->create($magento_orderId);
		Mage::getModel('sales/order')->loadByIncrementId($magento_orderId)->setState(Mage_Sales_Model_Order::STATE_CLOSED, true)->save();
		return true;
	}

	/**
	* Add tracking information to a order
	*/
	function create_ship_track($shipment_id, $carrier, $msg, $track_id)
	{
		$this->order_shipment = Mage::getSingleton('sales/order_shipment_api');
		return $this->order_shipment->addTrack($shipment_id, $carrier, $msg, $track_id);
	}

	/**
	* Send Shipment email to customer
	*/
	function send_ship_email($newShipmentId, $comment)
	{
		$this->order_shipment = Mage::getSingleton('sales/order_shipment_api');
		return $this->order_shipment->sendInfo($newShipmentId, $comment);
	}

	/** 
	* Add comment to a shipment
	*/

	function create_ship_comment($shipment_id, $comment, $send_email, $include_comment)
	{
		$this->order_shipment = Mage::getSingleton('sales/order_shipment_api');
		return $this->order_shipment->addComment($shipment_id, $comment, $send_email, $include_comment);
	}

	/** 
	* Add comment to Order
	*/
	function create_order_comment($order_id, $comment, $status = "complete", $notify = true)
	{
		$this->order = Mage::getSingleton('sales/order_api');
		return $this->order->addComment($order_id, $comment, $status, $notify);
	}

	/**
	* Create  order shipment
	*/
	function create_order_shipment($order_id, $info, $message = "Shipped", $send_email = true, $include_comment = true)
	{
		$this->order_shipment = Mage::getSingleton('sales/order_shipment_api');
		return $this->order_shipment->create($order_id, $info, $message, $send_email, $include_comment);
	}

	/**
	* Retrieve full order information
	*
	* @param string $orderIncrementId
	* @return array
	*/
	public function info($orderIncrementId)
	{
		$order = $this->_initOrder($orderIncrementId);
		
		if ($order->getGiftMessageId() > 0) {
			$order->setGiftMessage(Mage::getSingleton('giftmessage/message')->load($order->getGiftMessageId())->getMessage());
		}

		$result = $this->_getAttributes($order, 'order');

		$result['shipping_address'] = $this->_getAttributes($order->getShippingAddress(), 'order_address');
		$result['billing_address']  = $this->_getAttributes($order->getBillingAddress(), 'order_address');
		$result['items']            = array();

		foreach ($order->getAllItems() as $item) {
			if ($item->getGiftMessageId() > 0) {
				$item->setGiftMessage(Mage::getSingleton('giftmessage/message')->load($item->getGiftMessageId())->getMessage());
			}

			// We need to grab some saleswarp data from the related product
			$product = Mage::getModel('catalog/product')->load((int) $item['product_id']);
			
			foreach ($product->getData() as $k => $v) {
				if (strpos($k, 'saleswarp') !== false)
					$item[$k] = $v;
			}
			$item['prod_url'] = $product->getProductUrl();

			$result['items'][] = $this->_getAttributes($item, 'order_item');
		}
		
		$result['payment'] = $this->_getAttributes($order->getPayment(), 'order_payment');
		$result['status_history'] = array();
		
		foreach ($order->getAllStatusHistory() as $history) {
			$result['status_history'][] = $this->_getAttributes($history, 'order_status_history');
		}
		
		return $result;
	}

	/**
	* Genearate Refund for a order
	*/
	function refund_invoice($magento_orderId, $data)
	{
		$order = Mage::getModel('sales/order')->loadByIncrementId($magento_orderId);
		
		$inv = true;
		if (isset($data['inv'])) {
			$inv = false;
			unset($data['inv']);
		}
		$creditmemo = $this->_initCreditmemo($data, $magento_orderId, $inv);
		
		if (isset($data['do_refund'])) {
			$creditmemo->setRefundRequested(true);
		}
		if (isset($data['do_offline'])) {
			$creditmemo->setOfflineRequested((bool) (int) $data['do_offline']);
		}
		$creditmemo->register();
		if (!empty($data['send_email'])) {
			$creditmemo->setEmailSent(true);
		}
		$creditmemo->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
		$this->_saveCreditmemo($creditmemo);
		return true;
	}
	
	protected function _saveCreditmemo($creditmemo)
	{
		$transactionSave = Mage::getModel('core/resource_transaction')->addObject($creditmemo)->addObject($creditmemo->getOrder());
		if ($creditmemo->getInvoice()) {
			$transactionSave->addObject($creditmemo->getInvoice());
		}
		$transactionSave->save();
		return $this;
	}
	
	protected function _initInvoice($order, $invoiceId)
	{
		if ($invoiceId) {
			$invoice = Mage::getModel('sales/order_invoice')->load($invoiceId)->setOrder($order);
			if ($invoice->getId()) {
				return $invoice;
			}
		}
		return false;
	}
	
	protected function _getItemData($data)
	{
		if (isset($data['items'])) {
			$qtys = $data['items'];
		} else {
			$qtys = array();
		}
		return $qtys;
	}
	
	protected function _initCreditmemo($data, $orderId, $inv = true)
	{
		$creditmemo   = false;
		$creditmemoId = null;
		if ($creditmemoId) {
			$creditmemo = Mage::getModel('sales/order_creditmemo')->load($creditmemoId);
		} elseif ($orderId) {
			$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			if ($inv === true) {
				foreach ($order->getInvoiceCollection() as $invoice) {
					if ($invoice->canRefund()) {
						$invoiceId = $invoice->getId();
						break;
					}
				}
				$invoice = $this->_initInvoice($order, $invoiceId);
			} else {
				$invoice = false;
			}
			$savedData = $this->_getItemData($data);
			$qtys        = array();
			$backToStock = array();
			foreach ($savedData as $orderItemId => $itemData) {
				if (isset($itemData['qty'])) {
					$qtys[$orderItemId] = $itemData['qty'];
				}
				if (isset($itemData['back_to_stock'])) {
					$backToStock[$orderItemId] = true;
				}
			}
			$data['qtys'] = $qtys;
			

			$service = Mage::getModel('sales/service_order', $order);
			if ($invoice) {
				$creditmemo = $service->prepareInvoiceCreditmemo($invoice, $data);
			} else {
				$creditmemo = $service->prepareCreditmemo($data);
			}
		}
		Mage::register('current_creditmemo', $creditmemo);
		return $creditmemo;
	}
} // Class end
