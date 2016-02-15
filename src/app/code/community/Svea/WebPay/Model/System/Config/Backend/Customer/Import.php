<?php
/**
 * Backend model for customer address import CSV
 *
 */
class Svea_Webpay_Model_System_Config_Backend_Customer_Import extends Mage_Core_Model_Config_Data
{

    private $_fileHeaderMapping = array(
        'KundNr' => 'customerNumber',
        'Namn' => 'name',
        'OrgNr' => 'nationalIdNumber',
        'Status' => 'status',
        'SveaLimit' => 'sveaLimit',
        'KundLimit' => 'customerLimit',
        'RiskTyp' => 'riscType',
        'Skapad' => 'createdAt',
        'Kund Namn' => 'fullName',
        'Gatuadress' => 'street',
        'C/O Adress' => 'coAddress',
        'Postnr' => 'zipCode',
        'Husnr' => 'houseNumber',
        'Ort' => 'locality',
        'Landkod' => 'countryCode',
        'Nyckel' => 'addressSelector',
    );

    public function _afterSave()
    {
        if (!isset($_FILES['groups']['tmp_name']['svea_general']['fields']['import_customer_addresses']['value']) ||
            $_FILES['groups']['tmp_name']['svea_general']['fields']['import_customer_addresses']['value'] == '') {
            return $this;
        } else {
            try {
                $csvFile = $_FILES['groups']['tmp_name']['svea_general']['fields']['import_customer_addresses']['value'];
                $io     = new Varien_Io_File();
                $info   = pathinfo($csvFile);
                $io->open(array('path' => $info['dirname']));
                $io->streamOpen($info['basename'], 'r');

                // delete all current rows
                $resource = Mage::getSingleton('core/resource');
                $connection = $resource->getConnection('core_write');
                $connection->query("DELETE FROM {$resource->getTableName('svea_webpay/customer_address')}");

                // read header
                $header = array();
                foreach ($io->streamReadCsv(';') as $k => $v) {
                    $header[$k] = $this->_fileHeaderMapping[$this->_convertCsvString($v)];
                }

                // Import rows
                while ($row = $io->streamReadCsv(';')) {
                    $row = array_combine($header, $row);
                    foreach ($row as $k => $v) {
                        $row[$k] = $this->_convertCsvString($v);
                    }
                    if ($row['status'] !== 'Active') {
                        continue;
                    }

                    // Split name into first and last name
                    foreach ($this->_splitName($row['name']) as $k => $v) {
                        $row[$k] = $v;
                    }
                    unset($row['name']);

                    $model = Mage::getModel('svea_webpay/customer_address');
                    $model->setOrgnr($row['nationalIdNumber']);
                    $model->setCountryCode($row['countryCode']);
                    $model->setAddress($row);
                    $model->save();
                }
                $io->streamClose();
            } catch (Exception $e) {
                Mage::logException($e);
                throw new Mage_Exception(Mage::helper('svea_webpay')->__("Failed to import customer addresses."));
            }
        }
    }

    /**
     * Split an imported name into first and last name
     *
     * @returns array with 'firstName' and 'lastName' set
     */
    protected function _splitName($name)
    {
        $lastSpace = strrpos($name, ' ');
        if ($lastSpace === false) {
            return array(
                'firstName' => $name,
                'lastName' => $name,
            );
        } else {
            return array(
                'firstName' => substr($name, 0, $lastSpace),
                'lastName' => substr($name, $lastSpace + 1),
            );
        }
    }


    /**
     * Convert a string from the csv to an UTF-8 string
     *
     * @param $string A string from the csv
     *
     * @return UTF-8 string
     */
    protected function _convertCsvString($string)
    {
        $bom = pack('H*','EFBBBF');
        return trim(preg_replace("/^$bom/", '', $string));
    }

}
