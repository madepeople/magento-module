<?php
/**
 * Representing a response of a query
 *@package com.epayment.util.implementation.rest;
 */
class SveaQueryResponse extends SveaRestResponseBase{
	
	/**
	 * Queried transaction
	 * @var SveaTransactionQuery
	 */
	public $transaction;
	
	
	
	public function parse(){
		$xmlSrc = base64_decode($this->message);
		$xml = new SimpleXMLElement($xmlSrc);
		$this->statusCode = (string)$xml->statuscode;
		$this->transaction = new SveaTransactionQuery();
		$transaction = $xml->transaction;
		$this->transaction->transactionId = (string)$transaction['id'];
		$this->transaction->customerRefNo = (string)$transaction->customerrefno;
		$this->transaction->merchantId = (string)$transaction->merchantid;
		$this->transaction->amount = (string)$transaction->amount;
		$this->transaction->currency = (string)$transaction->currency;
		$this->transaction->vat = (string)$transaction->vat;
		$this->transaction->capturedAmount = (string)$transaction->capturedamount;
		$this->transaction->authorizedAmount = (string)$transaction->authorizedamount;
		$this->transaction->created = (string)$transaction->created;
		$this->transaction->creditStatus = (string)$transaction->creditstatus;
		$this->transaction->creditedAmount = (string)$transaction->creditedamount;
		$this->transaction->merchantResponseCode = (string)$transaction->merchantresponsecode;
		$this->transaction->paymentMethod = (string)$transaction->paymentmethod;
		$this->transaction->pdfLink = (string)$transaction->pdflink;
		$this->transaction->ssn = (string)$transaction->ssn;
		$this->transaction->legalName = (string)$transaction->legalname;
		$this->transaction->addressLine1 = (string)$transaction->addressline1;
		$this->transaction->addressLine2 = (string)$transaction->addressline2;
		$this->transaction->postcode = (string)$transaction->postcode;
		$this->transaction->postArea = (string)$transaction->postarea;
		$this->transaction->callbackUrl = (string)$transaction->callbackurl;
		$this->transaction->captureDate = (string)$transaction->captureddate;
		$this->transaction->cardType = (string)$transaction->cardtype;
		$this->transaction->maskedCardNo = (string)$transaction->maskedcardno;
		$this->transaction->eci = (string)$transaction->eci;
		$this->transaction->mdStatus = (string)$transaction->mdstatus;
		$this->transaction->expiryyear = (string)$transaction->expiryyear;
		$this->transaction->expirymonth = (string)$transaction->expirymonth;
		$this->transaction->ch_name = (string)$transaction->ch_name;
		$this->transaction->authCode = (string)$transaction->authcode;
		if($transaction->orderrows->count() > 0){
			$this->parseOrderRows($transaction->orderrows);
		}
	}
	
	protected function parseOrderRows($orderRows){
		foreach($orderRows->row as $row){
			$rowObj = new SveaOrderRow();
			$rowObj->id = (string)$row->id;
			$rowObj->amount = (string)$row->amount;
			$rowObj->description = (string)$row->description;
			$rowObj->name = (string)$row->name;
			$rowObj->quantity = (string)$row->quantity;
			$rowObj->sku = (string)$row->sku;
			$rowObj->unit = (string)$row->unit;
			$rowObj->vat = (string)$row->vat;
			$this->transaction->orderRows[] = $rowObj;
		}
	}
	
}