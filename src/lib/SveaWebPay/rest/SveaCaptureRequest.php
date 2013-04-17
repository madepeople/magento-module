<?php
/** Object representing a capture request
 *@package com.epayment.util.implementation.rest;
 */
class SveaCaptureRequest extends SveaRestRequestBase{
	
	public $transactionid;
	
	public function getResponseObject(){
		return new SveaConfirmResponse();
	}
	
	public function getUrl(){
		return "capture";
	}
	
	public function getRootElementName(){
		return "capture";
	}
	
	
}