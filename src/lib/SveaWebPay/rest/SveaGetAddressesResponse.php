<?php
class SveaGetAddressesResponse extends SveaRestResponseBase{
	
	/**
	 * List of addresses
	 * @var SveaAddress[]
	 */
	public $addresses;
	
	
	public function parse(){
		$xmlSrc = base64_decode($this->message);
		$xml = new SimpleXMLElement($xmlSrc);
		if(!empty($xml->address)){
			foreach($xml->address as $address){
				$add = new SveaAddress();
				$add->firstName = (string)$address->firstname;
				$add->lastName = (string)$address->lastname;
				$add->ssn = (string)$address->ssn;
				$add->addressLine1 = (string)$address->addressline1;
				$add->addressLine2 = (string)$address->addressline2;
				$add->postCode = (string)$address->postcode;
				$add->postArea = (string)$address->postarea;
				$add->addressId = (string)$address->addressid;
				$this->addresses[] = $add;
			}
		}
		
		
		
	}
	
}