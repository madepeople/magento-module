<?php

/**
 * SveaWebPay Payment Module for Magento.
 *   Copyright (C) 2012  SveaWebPay
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Any questions may be directed to kundtjanst.sveawebpay@sveaekonomi.se
 */
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Error
 *
 * @author Peter
 */
class SveaWebPay_Hosted_Model_Error
{
    private $errorCodes = array(
        // 0xx Payment result errors
        1   => 'The payment has been cancelled by the user before the transaction was completed.',
        2   => 'Invalid or incorrect information was provided by the user (such as personal identification number etc).',
        3   => 'Payment was rejected by the bank (the user lacks credit, reported as abusive etc).',
        4   => 'Internal system error such as databases are down, resources not available etc.',
        5   => 'External system error. A third party system was unable to service the payment request and reported an error.',
        // 1xx Missing malformed input parameters
        100 => 'Malformed input parameter "Encoding".',
        101 => 'Missing mandatory input parameter "OrderId".',
        102 => 'Missing mandatory input parameter "ResponseURL".',
        103 => 'Missing mandatory input parameter "CancelURL".',
        104 => 'Malformed input parameter "TestMode".',
        105 => 'Missing mandatory input parameter "Currency".',
        106 => 'Missing mandatory input parameter "Username".',
        107 => 'Missing mandatory input parameter "MD5".',
        108 => 'Malformed input parameter "Row#AmountExVat".',
        109 => 'Malformed input parameter "Row#VATPercentage".',
        110 => 'Malformed input parameter "Row#Quantity".',
        111 => 'Missing mandatory input parameter "Row#Description".',
        112 => 'No Order rows specified.',
        // 13x Missing malformed response parameters
        130 => 'Missing mandatory response parameter "Status".',
        131 => 'Missing mandatory response parameter "Status_code".',
        132 => 'Malformed response parameter "Status_code".',
        133 => 'Missing mandatory response parameter "Transaction_id".',
        134 => 'Malformed response parameter "Transaction_id".',
        // 2xx Internal system errors
        200 => 'Localization manager does not support the specified language "<languageOrLocale>" or country/region "<country>".',
        201 => 'Invalid payment method.',
        204 => 'Operation not supported for this type of payment method.',
        // 5xx Invalid credentials/authentication and user rights errors
        500 => 'Invalid username "<username>".',
        501 => 'Specified and computed authentication codes mismatch.',
        502 => 'Specified and computed authentication codes mismatch.',
        503 => 'Selected payment method not allowed.',
        504 => 'Selected payment method not allowed.',
        505 => 'Selected payment method can not be used',
        511 => 'Invalid context, you are not authorized to access this page.');
        
    public function convertFromCode($errorCode,$limit=true)
    {
        if (array_key_exists($errorCode,$this->errorCodes))
        {
            Mage::log('Error processing payment');
            Mage::log($errorCode . " -> " . $this->errorCodes[$errorCode]);
            if (($limit == true) && ($errorCode >= 100))
                return Mage::helper('sveawebpay')->__('Error processing payment');
            else
                return Mage::helper('sveawebpay')->__($this->errorCodes[$errorCode]);
        }
        return false;
    }
}
?>
