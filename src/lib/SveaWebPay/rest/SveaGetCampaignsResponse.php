<?php
/** Object representing a getCampaigns response
 *@package com.epayment.util.implementation.rest;
 */
class SveaGetCampaignsResponse extends SveaRestResponseBase{
	
	/**
	 * Array of available campaign objects
	 * @var SveaCampaign[]
	 */
	public $campaigns;
	
	public function parse(){
		$xmlSrc = base64_decode($this->message);
		$xml = new SimpleXMLElement($xmlSrc);
		$this->statusCode = (string)$xml->statuscode;
		foreach($xml->campaigns->campaign as $camp){
			$currCamp = new SveaCampaign();
			$currCamp->campaignCode = (string)$camp->campaigncode;
			$currCamp->description = (string)$camp->description;
			$currCamp->paymentPlanType = (string)$camp->paymentplantype;
			$currCamp->montlyAnnuity = (string)$camp->monthlyannuity;
			$currCamp->initialFee = (string)$camp->intialfee;
			$currCamp->notificationFee = (string)$camp->notificationfee;
			$currCamp->interestRatePercent = (string)$camp->interestratepercent;
			$currCamp->effectiveInterestRatePercent = (string)$camp->effectiveinterestratepercent;
			$this->campaigns[] = $currCamp;
		}
		
	}
	
	
}