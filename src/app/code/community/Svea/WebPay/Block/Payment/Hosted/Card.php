<?php

/**
 * Used to display information for "Hosted", ie gateway solutions
 */
class Svea_WebPay_Block_Payment_Hosted_Card extends Svea_WebPay_Block_Payment_Abstract
{
    protected $_template = 'svea/payment/hosted.phtml';

    protected function _construct()
    {
        $method = Mage::getModel('svea_webpay/hosted_card');
        $cardLogos = $method->getConfigData('card_logos');
        if (!empty($cardLogos)) {
            $cardLogos = explode(',', $cardLogos);
            $html = '';
            foreach ($cardLogos as $logo) {
                $html .= '<img class="svea-method-logo" src="' . $this->getSkinUrl('svea/payment_logos/svea_' . $logo . '.png') . '"> ';
            }
            $this->setMethodLabelAfterHtml($html);
        }

        return parent::_construct();
    }
}
