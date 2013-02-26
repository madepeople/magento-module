<?php 
/**
 * Representing a query request based on the transactions id
 *@package com.epayment.util.implementation.rest;
 */
class SveaQueryTransactionIdRequest extends SveaRestRequestBase{
	
	
	public $transactionid;
	
	
	/**
	 * (non-PHPdoc)
	 * @see SveaRestRequestBase::getUrl()
	 */
	public function getUrl(){
		return "querytransactionid";
	}
	/**
	 * (non-PHPdoc)
	 * @see SveaRestRequestBase::getRootElementName()
	 */
	public function getRootElementName(){
		return "query";
	} 
	
	public function getResponseObject(){
		return new SveaQueryResponse();
	}
	
	
}