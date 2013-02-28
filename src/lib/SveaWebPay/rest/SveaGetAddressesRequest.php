<?php
/** Object representing a getAddresses request
 *@package com.epayment.util.implementation.rest;
 */
class SveaGetAddressesRequest extends SveaRestRequestBase{
	
	public $ssn;
	
	public function getUrl(){
		return "getaddresses";
	}
	
	public function getRootElementName(){
		return "getaddresses";
	}
	public function getResponseObject(){
		return new SveaGetAddressesResponse();
	}
	
	
}