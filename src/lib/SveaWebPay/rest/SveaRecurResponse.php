<?php
/** Object representing a recur response
 *@package com.epayment.util.implementation.rest;
 */
class SveaRecurResponse extends SveaRestResponseBase{
	
	public $transactionId;
	public $customerRefNo;
	public $paymentMethod;
	public $merchantId;
	public $amount;
	public $vat;
	public $currency;
	public $cardType;
	public $maskedCardNo;
	public $expiryYear;
	public $expiryMonth;
	public $authCode;
	public $subscriptionId;
	
	
	public function parse(){
		$xmlSrc = base64_decode($this->message);
		$xml = new SimpleXMLElement($xmlSrc);
		$this->statusCode = (string)$xml->statuscode;
		$transaction = $xml->transaction;
		$this->transactionId = (string)$transaction['id'];
		$this->customerRefNo = (string)$transaction->customerrefno;
		$this->merchantId = (string)$transaction->merchantid;
		$this->amount = (string)$transaction->amount;
		$this->currency = (string)$transaction->currency;
		$this->vat = (string)$transaction->vat;
		$this->paymentMethod = (string)$transaction->paymentmethod;
		$this->cardType = (string)$transaction->cardType;
		$this->maskedCardNo = (string)$transaction->maskedcardno;
		$this->expiryYear = (string)$transaction->expiryyear;
		$this->expiryMonth = (string)$transaction->expirymonth;
		$this->authCode = (string)$transaction->authcode;
		$this->subscriptionId = (string)$transaction->subscriptionid;
		
	}
	
}