<?php
require_once Mage::getModuleDir('controllers', 'Saleswarp_Oms') . DS . "FastapiController.php";

class Saleswarp_WhitelistIP_FastapiController extends Saleswarp_Oms_FastapiController {

	/** 
     * Authentication API request
     *
     * @params string hash key
     * @return bool(trur or false)
     */
    public function ApiAuthentication($hash = null)
    {
        if(empty($hash)) {
            $this->authError = 'No hash key supplied';
            return false;
        } else {
            $mageHash 		= Mage::getStoreConfig('oms/registration/key');
            $useIpWhitelist = Mage::getStoreConfig('oms/ipwhitelist/enable');
            $allowedIPs 	= Mage::getStoreConfig('oms/ipwhitelist/list');
            
            // get user ip address
            $remoteIP 		= Mage::helper('core/http')->getRemoteAddr(false);
            if (!empty($mageHash) && $hash == $mageHash) {
                if ($useIpWhitelist && !in_array($remoteIP, explode(',',$allowedIPs))) {
                    $this->authError = 'IP not allowed';
                    return false;
                }
                return true;
            } else {
                $this->authError = 'Incorrect Hash key';
                return false;
            }
        }
    }
}