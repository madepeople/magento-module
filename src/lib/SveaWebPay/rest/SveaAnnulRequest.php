<?php 
/**
 * Representing a request to cancel an authorized payment
 *@package com.epayment.util.implementation.rest;
 */
class SveaAnnulRequest extends SveaRestRequestBase{
	
	
	public $transactionid;
	
	
	/**
	 * (non-PHPdoc)
	 * @see SveaRestRequestBase::getUrl()
	 */
	public function getUrl(){
		return "annul";
	}
	/**
	 * (non-PHPdoc)
	 * @see SveaRestRequestBase::getRootElementName()
	 */
	public function getRootElementName(){
		return "annul";
	} 
	
	public function getResponseObject(){
		return new SveaAnnulResponse();
	}
	
	
}