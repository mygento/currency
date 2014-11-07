<?php

class Mygento_Currency_Model_Cbrf extends Mage_Directory_Model_Currency_Import_Abstract {

    protected $_url = 'http://www.cbr.ru/scripts/XML_daily.asp';
    protected $_messages = array();

    /**
     * HTTP client
     *
     * @var Varien_Http_Client
     */
    protected $_httpClient;

    public function __construct() {
        $this->_httpClient = new Varien_Http_Client();
    }

    /**
     * Retrieve rate
     *
     * @param   string $currencyFrom
     * @param   string $currencyTo
     * @return  float
     */
    protected function _convert($currencyFrom, $currencyTo, $retry = 0) {

        if ($currencyFrom != 'RUB' && $currencyTo != 'RUB') {
            return null;
        }
        $direction = null;
        if ($currencyTo == 'RUB') {
            $direction = 'to';
        }
        if ($currencyFrom == 'RUB') {
            $direction = 'from';
        }
        if (!$direction) {
            return null;
        }

        try {
            $response = $this->_httpClient
                    ->setUri($this->_url)
                    ->setConfig(array('timeout' => Mage::getStoreConfig('currency/cbrf/timeout')))
                    ->request('GET')
                    ->getBody();

            $xml = simplexml_load_string($response, null, LIBXML_NOERROR);


            if (!$xml) {
                $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from %s.', $this->_url);
                return null;
            }

            foreach ($xml->Valute as $rate) {
                if ('to' == $direction && $currencyFrom == $rate->CharCode) {
                    $value = floatval(str_replace(',', '.', $rate->Value)) / floatval(str_replace(',', '.', $rate->Nominal));
                    if (Mage::getStoreConfig('currency/cbrf/fee')) {
                        $value += $value * (Mage::getStoreConfig('currency/cbrf/fee') / 100);
                    }
                    return $value;
                } elseif ('from' == $direction && $currencyTo == $rate->CharCode) {
                    $value = floatval(str_replace(',', '.', $rate->Nominal)) / floatval(str_replace(',', '.', $rate->Value));
                    if (Mage::getStoreConfig('currency/cbrf/fee')) {
                        $value += $value * (Mage::getStoreConfig('currency/cbrf/fee') / 100);
                    }
                    return $value;
                }
            }
        } catch (Exception $e) {
            if ($retry == 0) {
                $this->_convert($currencyFrom, $currencyTo, 1);
            } else {
                $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from %s.', $this->_url);
            }
        }
        return null;
    }

}
