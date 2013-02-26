<?php
/** Object representing a getPaymentMethods response
 *@package com.epayment.util.implementation.rest;
 */
class SveaGetPaymentMethodsResponse extends SveaRestResponseBase{
	
	/**
	 * List of configured paymentmethods
	 * @var string[]
	 */
	public $paymentMethods;
	
	
	public function parse(){
		$xmlSrc = base64_decode($this->message);
		$xml = new SimpleXMLElement($xmlSrc);
		$this->statusCode = (string)$xml->statuscode;
		foreach($xml->paymentmethods->paymentmethod as $pm){
			$this->paymentMethods[] = (string)$pm;
		}
	}
	
}