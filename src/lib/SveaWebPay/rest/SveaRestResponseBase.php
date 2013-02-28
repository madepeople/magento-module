<?php
/**
 * Class with base logic for all rest responses
 *@package com.epayment.util.implementation.rest;
 */
abstract class SveaRestResponseBase{
	
	/**
	 * Status code
	 * Status for this request
	 * @var Integer
	 */
	public $statusCode;
	
	protected $merchantId;
	protected $secret;
	protected $message;
	protected $mac;
	
	
	abstract function parse();
	
	public function __construct(){
		$config = SveaConfig::getConfig();
		$this->merchantId = $config->merchantId;
		$this->secret = $config->secret;
	}
	
	public function validateMac(){
		$calcMac = hash("SHA512", $this->message.$this->secret);
		return ($this->mac == $calcMac);
	}
	
	public function setMessage($message){
		$this->message = $message;
	}
	public function getMessage(){
		return $this->message;
	}
	public function setMac($mac){
		$this->mac = $mac;
	}
	public function getMac(){
		return $this->mac;
	}
	public function getMerchantId(){
		return $this->merchantId;
	}
	public function setMerchantId($merchantId){
		$this->merchantId = $merchantId;
	}
}