<?php
/**
 * Use this API to get/set key config data for the storefronts
 */
class Saleswarp_Publish_Model_Customer_Api extends Saleswarp_Oms_Model_Customer_Api
{
    
    public $activeStoreId = null;
    
    /** 
     * Test function
     */
    public function customer_api_test()
    {
        return "called Saleswarp_Publish_Model_Customer_Api, method->customer_api_test";
    }
    
    /** 
     * Get Items
     */
    public function items()
    {
        return parent::items();
    }
}
?>