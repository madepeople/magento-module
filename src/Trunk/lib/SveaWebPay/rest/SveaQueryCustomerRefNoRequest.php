<?php 
/**
 * Representing a query request based on customerrefno
 *@package com.epayment.util.implementation.rest;
 */
class SveaQueryCustomerRefNoRequest extends SveaRestRequestBase{
	
	
	public $customerrefno;
	
	
	/**
	 * (non-PHPdoc)
	 * @see SveaRestRequestBase::getUrl()
	 */
	public function getUrl(){
		return "querycustomerrefno";
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