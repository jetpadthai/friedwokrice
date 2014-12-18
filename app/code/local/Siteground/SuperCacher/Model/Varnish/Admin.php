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

class Siteground_SuperCacher_Model_Varnish_Admin {

	protected function _makePurgeRequest($command, Array $headers)
	{
		$ipFile = file_get_contents('/etc/sgcache_ip',true);
		if (!$ipFile)
			throw new Exception('Error: Could not find sgcache_ip!');

		// compile url string with scheme, domain/server and port
		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		$varnishIp = trim($ipFile);
		$uri = str_replace($_SERVER['HTTP_HOST'], $varnishIp, $baseUrl);

		$uri = str_replace('https://', 'http://', $uri); //dont do HTTPS request ever

		if (!isset($headers['X-Purge-Host']))
			$headers['X-Purge-Host'] = $_SERVER['HTTP_HOST'];

		// create HTTP client
		$client = new Zend_Http_Client();
		$client	->setUri($uri)
				->setHeaders($headers)
				->setConfig(array('timeout'=>15));

		// send PURGE request
		$response = $client->request($command);

		// check response
		if ($response->getStatus() != '200')
			throw new Exception('Error: Return status '.$response->getStatus());
	}

    /**
     * Flush all Magento URLs in Varnish cache
     *
     * @param  Siteground_SuperCacher_Model_Varnish_Configurator_Abstract $cfgr
     * @return bool
     */
    public function flushAll() {
        return $this->flushUrl( '.*' );
    }

    /**
     * Flush all Magento URLs matching the given (relative) regex
     *
     * @param  Siteground_SuperCacher_Model_Varnish_Configurator_Abstract $cfgr
     * @param  string $pattern regex to match against URLs
     * @return bool
     */
    public function flushUrl( $subPattern )
    {
    	$result = true;
    	try {
    		$headers = array(
    			'X-Purge-Regex'	=> $subPattern
    			);
    		$this->_makePurgeRequest('MAGENTOPURGE', $headers);
    	} catch( Mage_Core_Exception $e ) {
    		$result = $e->getMessage();
    	}

    	return $result;
    }

    /**
     * Flush according to Varnish expression
     *
     * @param  string $session
     * @param  string $event
     * @return bool
     */
    public function flushSessionEvent($session, $event)
    {
    	$result = true;
    	try {
    		$headers = array(
    			'X-SuperCacher-Flush-Session'	=> $session,
    			'X-SuperCacher-Flush-Events' => $event
    			);
    		$this->_makePurgeRequest('MAGENTOPURGESE', $headers);
    	} catch( Mage_Core_Exception $e ) {
    		$result = $e->getMessage();
    	}

    	return $result;
    }

    /**
     * Flush according to Varnish expression
     *
     * @param  string $event
     * @return bool
     */
    public function flushEvent($event)
    {
    	$result = true;
    	try {
    		$headers = array(
    				'X-SuperCacher-Flush-Events' => $event
    		);
    		$this->_makePurgeRequest('MAGENTOPURGEEV', $headers);
    	} catch( Mage_Core_Exception $e ) {
    		$result = $e->getMessage();
    	}

    	return $result;
    }

    /**
     * Flush all cached objects with the given content type
     *
     * @param  string $contentType
     * @return bool
     */
    public function flushContentType( $contentType ) {
    	$result = true;
    	try {
    		$headers = array(
    				'X-Content-Type'	=> $contentType
    		);
    		$this->_makePurgeRequest('MAGENTOPURGECT', $headers);
    	} catch( Mage_Core_Exception $e ) {
    		$result = $e->getMessage();
    	}

    	return $result;
    }
}
