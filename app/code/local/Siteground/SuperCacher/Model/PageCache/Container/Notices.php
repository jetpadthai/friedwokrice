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

class Siteground_SuperCacher_Model_PageCache_Container_Notices
    extends Enterprise_PageCache_Model_Container_Abstract {

    /**
     * Generate placeholder content before application was initialized and apply to page content if possible
     *
     * @param string $content
     * @return boolean
     */
    public function applyWithoutApp( &$content ) {
        return false;
    }

    /**
     * Render block content
     *
     * @return string
     */
    protected function _renderBlock() {
        $block = new Siteground_SuperCacher_Block_Notices();
        $block->setTemplate( 'supercacher/notices.phtml' );

        return $block->toHtml();
    }
}
