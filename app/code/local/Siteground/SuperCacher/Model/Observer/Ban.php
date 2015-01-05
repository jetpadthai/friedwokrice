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

class Siteground_SuperCacher_Model_Observer_Ban extends Varien_Event_Observer {

    /**
     * Cache the varnish admin object
     * @var Siteground_SuperCacher_Model_Varnish_Admin
     */
    protected $_varnishAdmin    = null;
    /**
     * Flag to prevent doing the ESI cache clear more than once per request
     * @var boolean
     */
    protected $_esiClearFlag    = array();

    /**
     * Clear the ESI block cache for a specific client
     *
     * Events:
     *     the events are applied dynamically according to what events are set
     *     for the various blocks' esi policies
     *
     * @param  Varien_Object $eventObject
     * @return null
     */
    public function banClientEsiCache( $eventObject ) {
        $eventName = $eventObject->getEvent()->getName();
        if( !in_array( $eventName, $this->_esiClearFlag ) ) {
            $sessionId = Mage::app()->getRequest()->getCookie( 'frontend' );
            if( $sessionId ) {
                $result = $this->_getVarnishAdmin()->flushSessionEvent( $sessionId, $eventName );
                Mage::dispatchEvent( 'supercacher_ban_client_esi_cache', array('result'=>$result) );
            }
            $this->_esiClearFlag[] = $eventName;
        }
    }

    public function banReviewDelete( $eventObject ) {

    	if (get_class($eventObject->getEvent()->getData('object')) == 'Mage_Review_Model_Review')
    	{
    		$result = $this->banProductReview( $eventObject );
    		Mage::dispatchEvent( 'supercacher_varnish_review_delete_after', array('result'=>$result) );

    	}
    	else
    		Mage::dispatchEvent( 'supercacher_varnish_review_delete_after');
    }

    public function banPoll( $eventObject ) {
    	if (get_class($eventObject->getEvent()->getData('object')) == 'Mage_Poll_Model_Poll')
    	{
    		$result = $this->_getVarnishAdmin()->flushEvent( 'poll_vote_add' );
    		Mage::dispatchEvent( 'supercacher_varnish_poll_save_after', array('result'=>$result) );

    	}
    	else
    		Mage::dispatchEvent( 'supercacher_varnish_poll_save_after');
    }
    /**
     * Ban a specific product page from the cache
     *
     * Events:
     *     catalog_product_save_commit_after
     *
     * @param  Varien_Object $eventObject
     * @return null
     */
    public function banProductPageCache( $eventObject ) {
        if( Mage::helper( 'supercacher/varnish' )->getVarnishEnabled() ) {
            $banHelper = Mage::helper( 'supercacher/ban' );
            $product = $eventObject->getProduct();
            $urlPattern = $banHelper->getProductBanRegex( $product );
            $result = $this->_getVarnishAdmin()->flushUrl( $urlPattern );
            Mage::dispatchEvent( 'supercacher_ban_product_cache', array('result'=>$result) );
            $cronHelper = Mage::helper( 'supercacher/cron' );
            if( $this->_checkResult( $result ) &&
                    $cronHelper->getCrawlerEnabled() ) {
                $cronHelper->addProductToCrawlerQueue( $product );
                foreach( $banHelper->getParentProducts( $product )
                        as $parentProduct ) {
                    $cronHelper->addProductToCrawlerQueue( $parentProduct );
                }
            }
        }
    }

    /**
     * Ban a product page from the cache if it's stock status changed
     *
     * Events:
     *     cataloginventory_stock_item_save_after
     *
     * @param  Varien_Object $eventObject
     * @return null
     */
    public function banProductPageCacheCheckStock( $eventObject ) {
        if( Mage::helper( 'supercacher/varnish' )->getVarnishEnabled() ) {
            $item = $eventObject->getItem();
            if( $item->getStockStatusChangedAutomatically() ||
                    ( $item->getOriginalInventoryQty() <= 0 &&
                        $item->getQty() > 0 &&
                        $item->getQtyCorrection() > 0 ) ) {
                $banHelper = Mage::helper( 'supercacher/ban' );
                $cronHelper = Mage::helper( 'supercacher/cron' );
                $product = Mage::getModel( 'catalog/product' )
                    ->load( $item->getProductId() );
                $urlPattern = $banHelper->getProductBanRegex( $product );
                $result = $this->_getVarnishAdmin()->flushUrl( $urlPattern );
                Mage::dispatchEvent( 'supercacher_ban_product_cache_check_stock',
                    array('result'=>$result) );
                if( $this->_checkResult( $result ) &&
                        $cronHelper->getCrawlerEnabled() ) {
                    $cronHelper->addProductToCrawlerQueue( $product );
                    foreach( $banHelper->getParentProducts( $product )
                            as $parentProduct ) {
                        $cronHelper->addProductToCrawlerQueue( $parentProduct );
                    }
                }
            }
        }
    }

    /**
     * Ban a category page, and any subpages on save
     *
     * Events:
     *     catalog_category_save_commit_after
     *
     * @param  Varien_Object $eventObject
     * @return null
     */
    public function banCategoryCache( $eventObject ) {
        if( Mage::helper( 'supercacher/varnish' )->getVarnishEnabled() ) {
            $category = $eventObject->getCategory();
            $result = $this->_getVarnishAdmin()->flushUrl( $category->getUrlKey() );
            Mage::dispatchEvent( 'supercacher_ban_category_cache', array('result'=>$result) );
            $cronHelper = Mage::helper( 'supercacher/cron' );
            if( $this->_checkResult( $result ) &&
                    $cronHelper->getCrawlerEnabled() ) {
                $cronHelper->addCategoryToCrawlerQueue( $category );
            }
        }
    }

    /**
     * Clear the media (CSS/JS) cache, corresponds to the buttons on the cache
     * page in admin
     *
     * Events:
     *     clean_media_cache_after
     *
     * @param  Varien_Object $eventObject
     * @return null
     */
    public function banMediaCache( $eventObject ) {
        if( Mage::helper( 'supercacher/varnish' )->getVarnishEnabled() ) {
            $result = $this->_getVarnishAdmin()->flushUrl( 'media/(?:js|css)/' );
            Mage::dispatchEvent( 'supercacher_ban_media_cache', array('result'=>$result) );
            $this->_checkResult( $result );
        }
    }

    /**
     * Flush catalog images cache, corresponds to same button in admin cache
     * management page
     *
     * Events:
     *     clean_catalog_images_cache_after
     *
     * @param  Varien_Object $eventObject
     * @return null
     */
    public function banCatalogImagesCache( $eventObject ) {
        if( Mage::helper( 'supercacher/varnish' )->getVarnishEnabled() ) {
            $result = $this->_getVarnishAdmin()->flushUrl(
                'media/catalog/product/cache/' );
            Mage::dispatchEvent( 'supercacher_ban_catalog_images_cache', array('result'=>$result) );
            $this->_checkResult( $result );
        }
    }

    /**
     * Ban a specific CMS page from cache after edit
     *
     * Events:
     *     cms_page_save_commit_after
     *
     * @param  Varien_Object $eventObject
     * @return null
     */
    public function banCmsPageCache( $eventObject ) {
        if( Mage::helper( 'supercacher/varnish' )->getVarnishEnabled() ) {
            $pageId = $eventObject->getDataObject()->getIdentifier();
            $result = $this->_getVarnishAdmin()->flushUrl( $pageId . '(?:\.html?)?$' );
            Mage::dispatchEvent( 'supercacher_ban_cms_page_cache', array('result'=>$result) );
            $cronHelper = Mage::helper( 'supercacher/cron' );
            if( $this->_checkResult( $result ) &&
                    $cronHelper->getCrawlerEnabled() ) {
                $cronHelper->addCmsPageToCrawlerQueue( $pageId );
            }
        }
    }

    /**
     * Do a full cache flush, corresponds to "Flush Magento Cache" and
     * "Flush Cache Storage" buttons in admin > cache management
     *
     * Events:
     *     adminhtml_cache_flush_system
     *     adminhtml_cache_flush_all
     *
     * @param  Varien_Object $eventObject
     * @return null
     */
    public function banAllCache( $eventObject ) {
        if( Mage::helper( 'supercacher/varnish' )->getVarnishEnabled() ) {
            $result = $this->_getVarnishAdmin()->flushAll();
            Mage::dispatchEvent( 'supercacher_ban_all_cache', array('result'=>$result) );
            $this->_checkResult( $result );
        }
    }

    /**
     * Do a flush on the ESI blocks
     *
     * Events:
     *     adminhtml_cache_refresh_type
     *
     * @param  Varien_Object $eventObject
     * @return null
     */
    public function banCacheType( $eventObject ) {
        switch( $eventObject->getType() ) {
            case Mage::helper( 'supercacher/esi' )->getMageCacheName():
                if( Mage::helper( 'supercacher/esi' )->getEsiEnabled() ) {
                    $result = $this->_getVarnishAdmin()->flushUrl(
                        '/supercacher/esi/getBlock/' );
                    Mage::dispatchEvent( 'supercacher_ban_esi_cache', array('result'=>$result) );
                    $this->_checkResult( $result );
                }
                break;
            case Mage::helper( 'supercacher/varnish' )->getMageCacheName():
                $this->banAllCache( $eventObject );
                break;
        }
    }

    /**
     * Ban a product's reviews page
     *
     * @param  Varien_Object $eventObject
     * @return bool
     */
    public function banProductReview( $eventObject ) {
        $patterns = array();
        $review = $eventObject->getObject();
        $products = $review->getProductCollection()->getItems();
        $productIds = array();
        foreach($products as $product)
        	$productIds[] = $product->getEntityId();

        $patterns[] = sprintf( '/review/product/list/id/(?:%s)/',implode( '|', array_unique( $productIds ) ) );

        $patterns[] = sprintf( '/review/product/list/id/(?:%s)/category/',
            implode( '|', array_unique( $productIds ) ) );
        $patterns[] = sprintf( '/review/product/view/id/%d/',
            $review->getEntityId() );
        $patterns[] = sprintf( '(?:%s)', implode( '|',
            array_unique( array_map(
                create_function( '$p',
                    'return $p->getUrlModel()->formatUrlKey( $p->getName() );' ),
                $products ) )
        ) );
        $urlPattern = implode( '|', $patterns );

        $result = $this->_getVarnishAdmin()->flushUrl( $urlPattern );
        return $this->_checkResult( $result );
    }

    /**
     * Check a result from varnish admin action, log if result has errors
     *
     * @param  array $result stored as $socketName => $result
     * @return bool
     */
    protected function _checkResult( $result ) {
        return $result === true;
    }

    /**
     * Get the varnish admin socket
     *
     * @return Siteground_SuperCacher_Model_Varnish_Admin
     */
    protected function _getVarnishAdmin() {
        if( is_null( $this->_varnishAdmin ) ) {
            $this->_varnishAdmin = Mage::getModel( 'supercacher/varnish_admin' );
        }
        return $this->_varnishAdmin;
    }
}
