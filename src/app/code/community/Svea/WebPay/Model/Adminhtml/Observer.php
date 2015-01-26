<?php

/**
 * Observer to check for newer versions when in the Admin Panel
 *
 * @author Péter Tóth <peter@madepeople.se>
 */
class Svea_WebPay_Model_Adminhtml_Observer extends Mage_Core_Model_Observer
{
    private $endpoint = 'https://api.github.com/repos/sveawebpay/magento-module/releases';
    private $cacheKey = 'Svea_WebPay_Releases';

    /**
     * Get JSON of releases from cache
     *
     * @return false|mixed
     */
    private function _getCachedReleases()
    {
        $cache = Mage::app()->getCache();
        return $cache->load($this->cacheKey);
    }

    /**
     * Save releases to cache
     *
     * @param string $releases JSON of the release data
     * @return bool
     */
    private function _saveReleasesToCache($releases)
    {
        $cache = Mage::app()->getCache();
        return $cache->save($releases, $this->cacheKey, array('CONFIG'));
    }

    /**
     * Check if there is a newer version of this module, display admin notice if there is
     */
    public function checkModuleVersion()
    {
        try {
            $releases = $this->_getCachedReleases();
            if (!$releases) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, "Svea WebPay module");
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $releases = curl_exec($ch);
                curl_close($ch);
                $this->_saveReleasesToCache($releases);
            }

            $releases = json_decode($releases);

            foreach ($releases as $release) {
                if ($release->draft === false && $release->prerelease === false) {
                    $latestVersion = $release->name;
                    $tarballUrl = $release->tarball_url;
                    break;
                }
            }

            $currentVersion = (string)Mage::getConfig()->getModuleConfig("Svea_WebPay")->version;

            if (version_compare($currentVersion, $latestVersion, '<')) {
                $title = 'New version of Svea WebPay available!';
                $description = 'There is a new version ' . $latestVersion . ' of Svea WebPay available for download.';
                $date = date('Y-m-d H:i:s');
                Mage::getModel('adminnotification/inbox')->getResource()
                    ->parse(new Mage_AdminNotification_Model_Inbox, array(array(
                        'severity'    => Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE,
                        'date_added'  => $date,
                        'title'       => $title,
                        'description' => $description,
                        'url'         => $tarballUrl,
                    )));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('adminhtml/session')->addWarning('Svea WebPay was not able to check for updates. Please read the error log for more information.');
        }
    }
}