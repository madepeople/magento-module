<?php
/**
 * Representing a transaction, often found in rest responses
 *@package com.epayment.util.implementation.rest;
 */
class SveaTransactionQuery{
	
	public $transactionId;
	public $customerRefNo;
	public $merchantId;
	public $amount;
	public $currency;
	public $vat;
	public $capturedAmount;
	public $authorizedAmount;
	public $created;
	public $creditStatus;
	public $creditedAmount;
	public $merchantResponseCode;
	public $paymentMethod;
	public $pdfLink;
	public $ssn;
	public $legalName;
	public $addressLine1;
	public $addressLine2;
	public $postcode;
	public $postArea;
	public $callbackUrl;
	public $captureDate;
	public $cardType;
	public $maskedCardNo;
	public $eci;
	public $mdStatus;
	public $expiryyear;
	public $expirymonth;
	public $ch_name;
	public $authCode;
	
	/**
	 * Orderrows
	 * @var SveaOrderRow[]
	 */
	public $orderRows;
	
	
}