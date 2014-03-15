<?php

/**
 * This might be handy for choosing between the countries that are available
 * to the Svea Service payment methods
 *
 * @author jonathan@madepeople.se
 */
class Svea_WebPay_Model_Adminhtml_System_Config_Source_Payment_Country
{
    protected $_options;

    public function toOptionArray($isMultiselect=false)
    {
        if (!$this->_options) {
            $options = Mage::getResourceModel('directory/country_collection')
                ->addFieldToFilter('country_id', array('in' => array(
                    'SE', 'NO', 'FI', 'DK', 'NL', 'DE'
                )))
                ->loadData()
                ->toOptionArray(false);

            $this->_options = array();
            foreach ($options as $option) {
                if ($option['value'] === 'SE') {
                    array_unshift($this->_options, $option);
                } else {
                    $this->_options[] = $option;
                }
            }
        }

        return $this->_options;
    }
}