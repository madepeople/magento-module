<?php

class Svea_WebPay_Model_Adminhtml_Observer extends Mage_Core_Model_Observer
{
    private $endpoint = 'https://api.github.com/repos/sveawebpay/magento-module/releases';
    private $cacheKey = 'Svea_WebPay_Releases';

    private function _getCachedReleases()
    {
        $cache = Mage::app()->getCache();
        return $cache->load($this->cacheKey);
    }

    private function _saveCachedReleases($releases)
    {
        $cache = Mage::app()->getCache();
        return $cache->save($this->cacheKey, $releases, array('CONFIG'));
    }

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
                $this->_saveCachedReleases($releases);
            }

            $releases = json_decode($releases);

            foreach ($releases as $release) {
                if ($release->draft === false && $release->prerelease === false) {
                    $latestVersion = $release->name;
                    $tarballUrl = $release->tarball_url;
                    break;
                }
            }

            $currentVersion = Mage::getConfig()->getModuleConfig("Svea_Webpay")->version;

            if (version_compare($currentVersion, $latestVersion, '<')) {
                Mage::getModel('adminnotification/inbox')->addNotice('New version of Svea WebPay', 'There is a new version of Svea WebPay available for download. Please upgrade!', $tarballUrl);
            }
        } catch (Exception $e) {
            Mage::log($e);
            Mage::getSingleton('adminhtml/session')->addWarning('Svea WebPay was not able to check for updates. Please read the error log for more information.');
        }
    }
}