<?php

/**
 * The purpose of this block is to render the address template returned by
 * the getAddress utility action
 *
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Block_Payment_Service_Address
    extends Mage_Core_Block_Abstract
{
    /**
     * Get the billing address from the quote if it hasn't been set externally.
     *
     * @return Mage_Sales_Model_Order_Address
     */
    public function getAddress()
    {
        if (!$this->hasData('address')) {
            $address = Mage::getSingleton('quote/session')
                ->getQuote()
                ->getBillingAddress();
            $this->setData('address', $address);
        }
        return $this->getData('address');
    }
    protected function _toHtml()
    {
        $address = $this->getAddress();
        $html =<<<EOF
                <strong style="display: block;border-bottom: 1px solid #A2B5C4;margin-bottom: 10px;">{$this->__('your_invoice_will_be_sent_to')}</strong>
                <div class="svea-select-container"></div>
                <address class=svea-address-element" style="font-style:14px; font-weight: bold;">
                    #{fullName}<br>
                    #{street}<br>
                    #{zipCode} #{locality}<br>
                    T: #{phoneNumber}
                </address>
EOF;
//                    {$address->getFirstname()} {$address->getLastname()}<br>
//                    {$address->getStreetFull()}<br>
//                    {$address->getPostcode()} {$address->getCity()}
        return $html;
    }
}