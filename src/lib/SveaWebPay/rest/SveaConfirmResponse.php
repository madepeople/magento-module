<?php
/** Object representing a confirm response
 *@package com.epayment.util.implementation.rest;
 */
class SveaConfirmResponse extends SveaRestResponseBase{
	
	public $transactionId;
	public $customerRefNo;
	
	public function parse(){
		$xmlSrc = base64_decode($this->message);
		$xml = new SimpleXMLElement($xmlSrc);
		$this->statusCode = (string)$xml->statuscode;
		$transaction = $xml->transaction;
		$this->transactionId = (string)$transaction['id'];
		$this->customerRefNo = (string)$transaction->customerrefno;
	}
	
}