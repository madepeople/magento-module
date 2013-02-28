<?php

	/*
	*	Class: SveaCalculation
	*	Filename: SveaCalculation.php
	*	Author: Christian Nedstedt
	*	Description: file is used to calculate some simple economicmathematics wich
	*		is not needed to be included anywhere else than in here.
	*		The main purpose of this class it to be able to calculate mixed taxes.
	*	Company: Svea Ekonomi
	*/
	
	class SveaCalculation {

		// Singleton instance variable.
		private static $_instance;
		
		// Singleton usage.
		public static function GetInstance() {
			if(!self::$_instance)
				self::$_instance = new SveaCalculation();
			return self::$_instance;
		}
		
		// Calculation information is stored in this array.
		private $_calculationArray = Array();
		
		/*
		*	This method exists for the user to add the information needed to calculate
		*	the tax amount for invoicefee and such, this is only needed in those cases that we have 
		*	mixed tax percentage, however it can be used to calculate tax even though it's not mixed.
		*	Parameters:
		*		$valueExcludingVAT value of product.
		*		$taxAmount of product.
		*/
		public function AddCalculationInformation($valueExcludingVAT,$taxAmount) {
			$taxPercentage = $this->GetTaxPercentage( $valueExcludingVAT,$taxAmount );
			if(!array_key_exists($taxPercentage,$this->_calculationArray))
				$this->_calculationArray[$taxPercentage] = Array("value" => 0,"tax" => 0);
			$this->_calculationArray[$taxPercentage]["value"] += $valueExcludingVAT;
			$this->_calculationArray[$taxPercentage]["tax"]   += $taxAmount;
		}
		
		/*
		*	Since this class is using singleton one might want to clear the calculation information
		*	between calls. This method is used for that.
		*/	
		public function ClearCalculationInformation() {
			unset($this->_calculationArray);
			$this->_calculationArray = Array();
		}
		
		/*
		*	Dependencies: This method depends on information stored in calculation array.
		*		Methods to run before this method is AddCalculationInformation.
		*	Parameters: $valueExcludingVAT is the value exluding vat of wich you want to calculate tax.
		*	Return: Tax amount value that should be instead of the tax that already exists. Example invoicefee tax.
		*/
		public function GetTaxAmountBasedOnValue($valueExcludingVAT) {
			$totalValue = 0;
			foreach($this->_calculationArray as $taxRate => $info)
				$totalValue += $info["value"];
			
			$totalTaxAmount = 0;
			foreach($this->_calculationArray as $taxPercentage => $info) {
				$negativeTaxRate = $this->GetNegativeTaxRate($taxPercentage);
				$totalTaxAmount += ($info["value"] / $totalValue) * $valueExcludingVAT * $negativeTaxRate;
			}
			return $totalTaxAmount;
		}
		
		/*

		*/
		public function GetTaxPercentage($valueExcludingVAT,$taxAmount) {
			return (int)(($taxAmount / $valueExcludingVAT) * 100.0);
		}
		
		/*
		*	Parameter: $taxPercentage tax in percentage form (25%).
		*	Return: returns a multiplication friendly number. 1.25
		*/
		public function GetTaxRate($taxPercentage) {
			return 1 + $this->GetNegativeTaxRate($taxPercentage);
		}
		
		/*
		*	Parameter: $taxPercentage tax in percentage form (25%).
		*	Return: returns a multiplication friendly number. 0.25
		*/
		public function GetNegativeTaxRate($taxPercentage) {
			return ($taxPercentage) / 100.0;
		}
	}
?>