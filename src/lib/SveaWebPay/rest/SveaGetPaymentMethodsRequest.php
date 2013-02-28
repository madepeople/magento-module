<?php
/** Object representing a getPaymentMethods request
 *@package com.epayment.util.implementation.rest;
 */
class SveaGetPaymentMethodsRequest extends SveaRestRequestBase{
	
	
	public function getUrl(){
		return "getpaymentmethods";
	}
	
	public function getRootElementName(){
		return "getpaymentmethods";
	}
	
	public function getResponseObject(){
		return new SveaGetPaymentMethodsResponse();
	}
	
}