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

class Siteground_SuperCacher_Model_Observer_Varnish extends Varien_Event_Observer {

	private function _performSgCacheCheck()
	{
		if(isset($_GET['sgCacheCheck']) && $_GET['sgCacheCheck'] == md5('magentoCheck'))
			die('OK');
	}

	/**
     * Check sentinel flags and set headers/cookies as needed
     *
     * Events: http_response_send_before
     *
     * @param  mixed $eventObject
     * @return null
     */
    public function setCacheFlagHeader( $eventObject ) {
		$this->_performSgCacheCheck();
    	$response = $eventObject->getResponse();

        if( Mage::helper( 'supercacher/varnish' )->shouldResponseUseVarnish() )
        	$response->setHeader( 'X-Cache-Enabled' ,'True');
		else
        	$response->setHeader( 'X-Cache-Enabled' ,'False');
    }

    /**
     * Add a rewrites for catalog/product_list_toolbar if config option enabled and the poll block
     *
     * @param Varien_Object $eventObject
     * @return null
     */
    public function addBlockRewrites( $eventObject ) {
        if( Mage::helper( 'supercacher/varnish' )->shouldFixProductListToolbar() ) {
            Mage::getSingleton( 'supercacher/shim_mage_core_app' )
                ->shim_addClassRewrite( 'block', 'catalog', 'product_list_toolbar',
                    'Siteground_SuperCacher_Block_Catalog_Product_List_Toolbar' );
        }

        Mage::getSingleton( 'supercacher/shim_mage_core_app' )
        	->shim_addClassRewrite( 'block', 'poll', 'activePoll',
        		'Siteground_SuperCacher_Block_Poll_ActivePoll' );
    }

    /**
     * Re-apply and save Varnish configuration on config change
     *
     * @param  mixed $eventObject
     * @return null
     */
    public function adminSystemConfigChangedSection( $eventObject ) {
        if(!Mage::helper( 'supercacher/varnish' )->getVarnishEnabled())
        {
        	$result = Mage::getModel( 'supercacher/varnish_admin' )->flushAll();
        	$session = Mage::getSingleton( 'core/session' );
        	if ($result === true)
        		$session->addSuccess( Mage::helper( 'supercacher/data' )->__( 'SuperCacher successfully disabled!' ) );
        	else
        		$session->addSuccess( Mage::helper( 'supercacher/data' )->__( 'Failed flushing all SuperCacher cache! Some pages might still be cached.' ) );
        }
    }
}
