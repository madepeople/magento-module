<?php
/**
 * Base class with base logic for all Rest requests
 *@package com.epayment.util.implementation.rest;
 */
abstract class SveaRestRequestBase{
	
	protected $message;
	protected $merchantId;
	protected $mac;
	
	const REST_URL_TEST = "https://dev.sveaekonomi.se/webpay/rest/";
	const REST_URL_PROD = "https://webpay.sveaekonomi.se/webpay/rest/";
	
	/**
	 * Returns requestURL suffix for the REST request in hand eg. "credit"
	 */
	abstract public function getUrl();
	/**
	 * Returns the root element of the request XML
	 */
	abstract public function getRootElementName();
	
	/**
	 * Return response object for parsing
	 * @return SveaRestResponseBase
	 */
	abstract public function getResponseObject();
	
	/**
	 * Gets URL from child class and appends base url according to testmode from SveaConf
	 */
	protected function _getUrl(){
		if(SveaConfig::getConfig()->testMode){
			return self::REST_URL_TEST.$this->getUrl();
		}else{
			return self::REST_URL_PROD.$this->getUrl();
		}
	}
	
	
	/**
	 * Returns an array with curl configurations
	 */
	protected function getDefaultCurlOptions(){
		return array( 
	        CURLOPT_POST => 1, 
	        CURLOPT_HEADER => 0, 
	        CURLOPT_URL => $this->_getUrl(), 
	        CURLOPT_FRESH_CONNECT => 1, 
	        CURLOPT_RETURNTRANSFER => 1, 
	        CURLOPT_FORBID_REUSE => 1, 
	        CURLOPT_SSL_VERIFYPEER => false,
	        CURLOPT_TIMEOUT => 10,
	        CURLOPT_POSTFIELDS => http_build_query(
							        	array(
							        		'message'		=>	$this->message,
							        		'merchantid'	=>	$this->merchantId,
							        		'mac'			=>	$this->mac
							        		),'','&'
	        		) 
	    );
	}
	/**
	 * Sets Merchant details from SveaConfig and creates the request message
	 * Enter description here ...
	 */
	protected function createMessage(){
		
		$xmlBuilder = new SveaXMLBuilder();
		$secret = SveaConfig::getConfig()->secret;
		$this->merchantId = SveaConfig::getConfig()->merchantId;
		$xml = $xmlBuilder->serializeRestRequest($this);
		$this->message = base64_encode(trim($xml));
		$this->mac = hash("SHA512", $this->message.$secret);
	}
	
	/**
	 * Make your request to SveaWebPay
	 * @throws RuntimeException On curl error
	 */
	public function doRequest(){
		$this->createMessage();
		$curl = curl_init();
	    curl_setopt_array($curl, $this->getDefaultCurlOptions());
	    $result = curl_exec($curl);
	    if(curl_errno($curl) > 0){
	    	throw new RuntimeException(curl_error($curl));
	    }
	    $xml = new SimpleXMLElement($result);
	    $response = $this->getResponseObject();
	    $response->setMac((string)$xml->mac);
	    $response->setMessage((string)$xml->message);
	    $response->setMerchantId((string)$xml->merchantid);
	    $response->setMessage((string)$xml->message);
	    $response->parse();
	    return $response;
	}
	
	
	public function getMessage(){
		return $this->message;
	}
	public function setMessage($message){
		$this->message = $message;
	}
	public function setMerchantId($merchantId){
		$this->merchantId = $merchantId;
	}
	public function getMerchantId(){
		return $this->merchantId;
	}
	public function getMac(){
		return $this->mac;
	}
	public function setMac($mac){
		return $this->mac;
	}
	
}