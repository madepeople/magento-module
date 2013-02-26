<?php
/**
 *@package com.epayment.util.implementation;
 */

class SveaOrder {
	
	/**
	 * Set your reference for this payment, must be unique
	 * @var String max 64 chars
	 */
	public $customerRefno;
	/**
	 * Payment method to use. Defaults to our "paypage" where the customer may choose
	 * @var String|const
	 */
	public $paymentMethod = self::PAYPAGE;
	/**
	 * Amount to pay, given in cents. Incl. VAT
	 * @var int
	 */
	public $amount;
	/**
	 * VAT amount given in cents.
	 * @var string
	 */
	public $vat;
	/**
	 * The URL to where the customer will be redirected after the payment
	 * @var string
	 */
	public $returnUrl;
	/**
	 * Currency code according to ISO 4217
	 * @var string
	 */
	public $currency;
	
	/**
	 * Array of orderrows
	 * @var SveaOrderRow[]
	 */
	public $orderRows;
	/**
	 * Additional xml params. Eg ssn for invoice payments. (ssn can be entered by the customer on our paypage)
	 * @var Array<string,string>
	 */
	public $params;
	
	/**
	 * Payment methods to exclude on PAYPAGE
	 * @var String[]
	 */
	public $excludePaymentMethods;

	
	const version = "1.2.0";
	
	const PAYPAGE = "PAYPAGE";
	const CARD = "CARD";
	const DBSHBSE = "DBSHBSE";
	const DBSWEDBANKSE = "DBSWEDBANKSE";
	const DBSEBSE = "DBSEBSE";
	const DBDANSKEBANKSE = "DBDANSKEBANKSE";
	const DBNORDEASE = "DBNORDEASE"; 
	const SVEASPLITSE = "SVEASPLITSE";
	const SVEAINVOICESE = "SVEAINVOICESE";
	
	public function __construct($customerRefno=null) {
		$this->customerRefno = $customerRefno;
		$this->orderRows = array();
	}
	
	/**
	 * Add an order row object
	 * @param SveaOrderRow $orderRow
	 */
	public function addOrderRow($orderRow){
		array_push($this->orderRows, $orderRow);
	}
	
	/**
	 * Add an additional param to the order xml. Eg ssn for invoice payments. (ssn can be entered by the customer on our paypage)
	 * @param string $name
	 * @param string $value
	 */
	public function setParam($name,$value){
		$this->params[$name] = $value;
	}
	
	/**
	 * Add a payment method to exclude. Use the defined constants
	 * @param String|const|String[] $paymentMethod
	 */
	public function excludePaymentMethod($paymentMethod){
		if(is_array($paymentMethod)){
			$this->excludePaymentMethods = array_merge($this->excludePaymentMethods , $paymentMethod);
		}else{
			$this->excludePaymentMethods[] = $paymentMethod;
		}
	}

}