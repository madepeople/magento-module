<?php
class SveaRestPaymentResponse extends SveaRestResponseBase{
	
	public $transactionId;
	public $paymentMethod;
	public $merchantId;
	public $customerRefno;
	public $amount;
	public $currency;
	public $legalName;
	public $ssn;
	public $addressLine1;
	public $addressLine2;
	public $postCode;
	public $postArea;
	public $cardType;
	public $maskedCardNo;
	public $authCode;
	
	
	
	public function parse(){
		$xmlSrc = base64_decode($this->message);
		$xml = new SimpleXMLElement($xmlSrc);
		$this->statusCode = (string)$xml->statuscode;
		if(!empty($xml->transaction)){
			$this->transactionId = (string)$xml->transaction["id"];
			$this->paymentMethod = (string)$xml->transaction->paymentmethod;
			$this->merchantId = (string)$xml->transaction->merchantid;
			$this->customerRefno = (string)$xml->transaction->customerrefno;
			$this->amount = (string)$xml->transaction->amount;
			$this->currency = (string)$xml->transaction->currency;
			$this->legalName = (string)$xml->transaction->customer->legalname;
			$this->ssn = (string)$xml->transaction->customer->ssn;
			$this->addressLine1 = (string)$xml->transaction->customer->addressline1;
			$this->addressLine2 = (string)$xml->transaction->customer->addressline2;
			$this->postCode = (string)$xml->transaction->customer->postcode;
			$this->postArea = (string)$xml->transaction->customer->postarea;
			$this->cardType = (string)$xml->transaction->cardtype;
			$this->maskedCardNo = (string)$xml->transaction->maskedcardno;
			$this->authCode = (string)$xml->transaction->authcode;
			
		}
		
	}
	
}