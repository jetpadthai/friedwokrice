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

class Siteground_SuperCacher_Block_Poll_ActivePoll extends
    Mage_Poll_Block_ActivePoll {

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
		/*
		 * Dirty hack to everride the template, and avoid nested esi loop
		 */
    	if ($this->getTemplate() == 'supercacher/esi.phtml')// && (strpos($_SERVER['REQUEST_URI'], 'supercacher/esi') === false))
    	{
    		$this->_templates['results'] = 'supercacher/esi.phtml';
    		$this->_templates['poll'] = 'supercacher/esi.phtml';
    	}

    	return parent::_toHtml();
    }
}
