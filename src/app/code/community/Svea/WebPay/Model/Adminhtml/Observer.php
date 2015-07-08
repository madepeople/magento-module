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
     * Check if there is a newer version of this module, display admin notice
     * if there is one.
     */
    public function checkModuleVersion()
    {
        if (!Mage::getStoreConfigFlag('payment/svea_general/check_for_new_updates')) {
            return;
        }

        try {
            $releases = $this->_getCachedReleases();
            if (false === $releases) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $this->endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, "Svea WebPay module");
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $releases = curl_exec($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($status >= 400) {
                    return;
                }

                if (false === $releases) {
                    $this->_saveReleasesToCache(json_encode(array()));
                    return;
                } else {
                    $this->_saveReleasesToCache($releases);
                }
            }

            $releases = json_decode($releases);
            if (is_array($releases) && count($releases) === 0) {
                // If it failed previously we don't want to fail consecutive
                // requests, since that takes time and logs errors.
                return;
            }

            foreach ($releases as $release) {
                if ($release->draft === false && $release->prerelease === false) {
                    $latestVersion = $release->name;
                    $htmlUrl = $release->html_url;
                    break;
                }
            }

            $currentVersion = (string)Mage::getConfig()
                ->getModuleConfig('Svea_WebPay')
                ->version;

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
                        'url'         => $htmlUrl,
                    )));
            }
        } catch (Exception $e) {
            $this->_saveReleasesToCache(json_encode(array()));
            Mage::logException($e);
        }
    }
}