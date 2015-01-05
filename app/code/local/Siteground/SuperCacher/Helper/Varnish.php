<?php

/**
 * siteground.com SuperCacher Extension for Magento, based on Nexcess.net Turpentine Extension for Magento
 * Copyright (C) 2013 SiteGround Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

class Siteground_SuperCacher_Helper_Varnish extends Mage_Core_Helper_Abstract {

    const MAGE_CACHE_NAME           = 'supercacher_pages';

    protected static $_hiddenBannedUrls = array(
    		'customer/account',
    		'customer/address',
    		'sales/order',
    		'sales/billing_agreement',
    		'sales/recurring_profile',
    		'review/customer',
    		'tag/customer',
    		'wishlist/',
    		'oauth/customer_token',
    		'newsletter/manage',
		'downloadable/customer/products',
    		'checkout/',
    		'moneybookers',
    		'paypal',
    		'googlecheckout/',
    		'pagseguro/',
		'epay/standard'
    );

    /**
     * Get whether Varnish caching is enabled or not
     *
     * @return bool
     */
    public function getVarnishEnabled() {
        return Mage::getStoreConfig( 'supercacher_varnish/general/enable_varnish' );
    }

    /**
     * Get whether Varnish debugging is enabled or not
     *
     * @return bool
     */
    public function getVarnishDebugEnabled() {
        return false;
    }

    /**
     * Check if the request passed through Varnish (has the correct secret
     * handshake header)
     *
     * @return boolean
     */
    public function isRequestFromVarnish() {
        return $this->getSecretHandshake() ==
            Mage::app()->getRequest()->getHeader( 'X-Supercacher-Secret-Handshake' );
    }

    /**
     * Check if Varnish should be used for this request
     *
     * @return bool
     */
    public function shouldResponseUseVarnish() {
        return $this->getVarnishEnabled() && $this->isRequestFromVarnish() && !$this->isUrlBlacklisted();
    }

    /**
     * Check if url is in caching blacklist
     *
     * @return bool
     */
    public function isUrlBlacklisted() {
        $blacklistRegexArray = explode("\n",Mage::getStoreConfig( 'supercacher_varnish/general/url_blacklist' ));
        $blacklistRegexArray = array_merge($blacklistRegexArray,self::$_hiddenBannedUrls);
        foreach($blacklistRegexArray as $key=>$row)
        	$blacklistRegexArray[$key] = preg_quote(trim($row),'/');
        $blacklistRegex = '/('.implode('|',$blacklistRegexArray) . ')/i';

        return preg_match($blacklistRegex, $_SERVER['REQUEST_URI']);
    }

    /**
     * Get the secret handshake value
     *
     * @return string
     */
    public function getSecretHandshake() {
        return '1';
    }

    /**
     * Get a Varnish management socket
     *
     * @param  string $host           [description]
     * @param  string|int $port           [description]
     * @param  string $secretKey=null [description]
     * @param  string $version=null   [description]
     * @return Siteground_SuperCacher_Model_Varnish_Admin_Socket
     */
    public function getSocket( $host, $port, $secretKey=null, $version=null ) {
        $socket = Mage::getModel( 'supercacher/varnish_admin_socket',
            array( 'host' => $host, 'port' => $port ) );
        if( $secretKey ) {
            $socket->setAuthSecret( $secretKey );
        }
        if( $version ) {
            $socket->setVersion( $version );
        }
        return $socket;
    }

    /**
     * Get the cache type Magento uses
     *
     * @return string
     */
    public function getMageCacheName() {
        return self::MAGE_CACHE_NAME;
    }

    /**
     * Get the configured default object TTL
     *
     * @return string
     */
    public function getDefaultTtl() {
        return 3600;
    }

    /**
     * Check if the product list toolbar fix is enabled and we're not in the
     * admin section
     *
     * @return bool
     */
    public function shouldFixProductListToolbar() {
        return Mage::helper( 'supercacher/data' )->useProductListToolbarFix() &&
            Mage::app()->getStore()->getCode() !== 'admin';
    }

    /**
     * Check if the Varnish bypass is enabled
     *
     * @return boolean
     */
    public function isBypassEnabled() {
        $cookieName     = Mage::helper( 'supercacher' )->getBypassCookieName();
        $cookieValue    = (bool)Mage::getModel( 'core/cookie' )->get($cookieName);

        return $cookieValue;
    }

    /**
     * Check if the notification about the Varnish bypass must be displayed
     *
     * @return boolean
     */
    public function shouldDisplayNotice() {
        return $this->getVarnishEnabled() && $this->isBypassEnabled();
    }
}
