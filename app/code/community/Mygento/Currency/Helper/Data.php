<?php

class Mygento_Currency_Helper_Data extends Mage_Core_Helper_Abstract {

    public function AddLog($text) {
        if (Mage::getStoreConfig('mycurrency/general/debug')) {
            Mage::log($text, null, 'mycurrency.log');
        }
    }

}