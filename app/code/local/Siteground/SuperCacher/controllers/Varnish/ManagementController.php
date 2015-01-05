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

class Siteground_SuperCacher_Varnish_ManagementController
    extends Mage_Adminhtml_Controller_Action {

    /**
     * Management index action, displays buttons/forms for config and cache
     * management
     *
     * @return null
     */
    public function indexAction() {
        $this->_title($this->__('System'))
            ->_title(Mage::helper('supercacher')->__('Varnish Management'));
        $this->loadLayout()
            ->_setActiveMenu('system/supercacher')
            ->_addContent($this->getLayout()
                ->createBlock('supercacher/management'))
            ->renderLayout();
    }

    /**
     * Full flush action, flushes all Magento URLs in Varnish cache
     *
     * @return null
     */
    public function flushAllAction() {
        Mage::dispatchEvent( 'supercacher_varnish_flush_all' );
        $result = Mage::getModel( 'supercacher/varnish_admin' )->flushAll();

        if ($result === true)
        	$this->_getSession()->addSuccess( Mage::helper( 'supercacher/data' )->__( 'Flushed SuperCacher cache!' ) );
        else
        	$this->_getSession()->addError( Mage::helper( 'supercacher/data' )->__( 'Error flushing SuperCacher!' ) );

        $this->_redirect( '*/cache' );
    }

    /**
     * Partial flush action, flushes Magento URLs matching "pattern" in POST
     * data
     *
     * @return null
     */
    public function flushPartialAction() {
        $postData = $this->getRequest()->getPost();
        if( !isset( $postData['pattern'] ) ) {
            $this->_getSession()->addError( $this->__( 'Missing URL post data' ) );
        } else {
            $pattern = $postData['pattern'];
            Mage::dispatchEvent( 'supercacher_varnish_flush_partial',
                array( 'pattern' => $pattern ) );
            $result = Mage::getModel( 'supercacher/varnish_admin' )
                ->flushUrl( $pattern );

	        if ($result === true)
	        	$this->_getSession()->addSuccess( Mage::helper( 'supercacher/data' )->__( 'Flushed matching URLs!' ) );
	        else
	        	$this->_getSession()->addError( Mage::helper( 'supercacher/data' )->__( 'Error flushing matching URLs!' ) );
        }
        $this->_redirect( '*/cache' );
    }

    /**
     * Flush objects by content type (ctype in POST)
     *
     * @return null
     */
    public function flushContentTypeAction() {
        $postData = $this->getRequest()->getPost();
        if( !isset( $postData['ctype'] ) ) {
            $this->_getSession()->addError( $this->__( 'Missing URL post data' ) );
        } else {
            $ctype = $postData['ctype'];
            Mage::dispatchEvent( 'supercacher_varnish_flush_content_type',
                array( 'ctype' => $ctype ) );
            $result = Mage::getModel( 'supercacher/varnish_admin' )
                ->flushContentType( $ctype );

            if ($result === true)
            	$this->_getSession()->addSuccess( Mage::helper( 'supercacher/data' )->__( 'Flushed matching content-types!' ) );
            else
            	$this->_getSession()->addError( Mage::helper( 'supercacher/data' )->__( 'Error flushing matching content-types!' ) );
        }
        $this->_redirect('*/cache');
    }

    /**
     * Activate or deactivate the Varnish bypass
     *
     * @return void
     */
    public function switchNavigationAction() {
        $type = $this->getRequest()->get( 'type' );
        if( is_null( $type ) ) {
            $this->_redirect( 'noRoute' );
            return;
        }

        $cookieName     = Mage::helper( 'supercacher' )->getBypassCookieName();
        $cookieModel    = Mage::getModel( 'core/cookie' );
        $adminSession   = Mage::getSingleton( 'adminhtml/session' );

        switch( $type ) {
            case 'default':
                $cookieModel->set( $cookieName,
                    Mage::helper( 'supercacher/varnish' )->getSecretHandshake() );
                $adminSession->addSuccess( Mage::helper( 'supercacher/data' )
                    ->__( 'The Varnish bypass cookie has been successfully added.' ) );
            break;

            case 'varnish':
                $cookieModel->delete( $cookieName );
                $adminSession->addSuccess( Mage::helper( 'supercacher/data' )
                    ->__( 'The Varnish bypass cookie has been successfully removed.' ) );
            break;

            default:
                $adminSession->addError( Mage::helper( 'supercacher/data' )
                    ->__( 'The given navigation type is not supported!' ) );
            break;
        }

        $this->_redirectReferer();
    }

    /**
     * Check if a visitor is allowed access to this controller/action(?)
     *
     * @return boolean
     */
    protected function _isAllowed() {
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/supercacher');
    }
}