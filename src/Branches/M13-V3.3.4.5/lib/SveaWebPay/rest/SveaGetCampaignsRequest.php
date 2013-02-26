<?php
/** Object representing a getCampaigns request
 *@package com.epayment.util.implementation.rest;
 */
class SveaGetCampaignsRequest extends SveaRestRequestBase{
	
	public $amount;
	public $vat;
	
	public function getResponseObject(){
		return new SveaGetCampaignsResponse();
	}
	
	public function getUrl(){
		return "getcampaigns";
	}
	
	public function getRootElementName(){
		return "getcampaigns";
	}
	
	
}