<?php
/** Object representing a confirm request
 *@package com.epayment.util.implementation.rest;
 */
class SveaConfirmRequest extends SveaRestRequestBase{
	
	public $transactionid;
	public $capturedate;
	
	public function getResponseObject(){
		return new SveaConfirmResponse();
	}
	
	public function getUrl(){
		return "confirm";
	}
	
	public function getRootElementName(){
		return "confirm";
	}
	
	
}