<?php
class SveaRestPaymentRequest extends SveaRestRequestBase{
	
	
	/**
	 * Svea Order object (responseURL is not needed for rest payments)
	 * @var SveaOrder
	 */
	public $order;
	
	
	public function getUrl(){
		return "payment";
	}
	
	public function getRootElementName(){
		return "payment";
	}
	
	public function getResponseObject(){
		return new SveaRestPaymentResponse();
	}
	
}