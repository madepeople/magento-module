<?php 
/** Object representing a recur request
 *@package com.epayment.util.implementation.rest;
 */
class SveaRecurRequest extends SveaRestRequestBase{
	
	
	public $subscriptionid;
	public $amount;
	public $customerrefno;
	public $currency;
	
	
	/**
	 * (non-PHPdoc)
	 * @see SveaRestRequestBase::getUrl()
	 */
	public function getUrl(){
		return "recur";
	}
	/**
	 * (non-PHPdoc)
	 * @see SveaRestRequestBase::getRootElementName()
	 */
	public function getRootElementName(){
		return "recur";
	} 
	
	public function getResponseObject(){
		return new SveaRecurResponse();
	}
	
	
}