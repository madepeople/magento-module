<?php 
/** Object representing a credit request
 *@package com.epayment.util.implementation.rest;
 */
class SveaCreditRequest extends SveaRestRequestBase{
	
	
	public $transactionid;
	public $amounttocredit;
	
	
	/**
	 * (non-PHPdoc)
	 * @see SveaRestRequestBase::getUrl()
	 */
	public function getUrl(){
		return "credit";
	}
	/**
	 * (non-PHPdoc)
	 * @see SveaRestRequestBase::getRootElementName()
	 */
	public function getRootElementName(){
		return "credit";
	} 
	
	public function getResponseObject(){
		return new SveaCreditResponse();
	}
	
	
}