<?php
/**
 * @category   Apptrian
 * @package    Apptrian_Minify
 * @author     Apptrian
 * @copyright  Copyright (c) 2014 Apptrian (http://www.apptrian.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Apptrian_Minify_Block_Page_Html_Head extends Mage_Page_Block_Html_Head
{
	
	/**
	 * Override method.
	 * 
	 * @param string $format
	 * @param array $staticItems
	 * @param array $skinItems
	 * @param string $mergeCallback
	 * @return string
	 */
	protected function &_prepareStaticAndSkinElements($format, array $staticItems, array $skinItems,
			$mergeCallback = null)
	{
		$designPackage = Mage::getDesign();
		$baseJsUrl = Mage::getBaseUrl('js');
		$items = array();
		if ($mergeCallback && !is_callable($mergeCallback)) {
			$mergeCallback = null;
		}
	
		// get static files from the js folder, no need in lookups
		foreach ($staticItems as $params => $rows) {
			foreach ($rows as $name) {
				$items[$params][] = $mergeCallback ? Mage::getBaseDir() . DS . 'js' . DS . $name : $baseJsUrl . $name;
			}
		}
	
		// lookup each file basing on current theme configuration
		foreach ($skinItems as $params => $rows) {
			foreach ($rows as $name) {
				$items[$params][] = $mergeCallback ? $designPackage->getFilename($name, array('_type' => 'skin'))
				: $designPackage->getSkinUrl($name, array());
			}
		}
	
		$html = '';
		foreach ($items as $params => $rows) {
			// attempt to merge
			$mergedUrl = false;
			if ($mergeCallback) {
				$mergedUrl = call_user_func($mergeCallback, $rows);
			}
			// render elements
			$params = trim($params);
			$params = $params ? ' ' . $params : '';
			if ($mergedUrl) {
				
				$minifiedFileUrl = $this->minifyCssJs($format, $mergedUrl, $params);

				$html .= sprintf($format, $minifiedFileUrl, $params);
				
			} else {
				foreach ($rows as $src) {
					$html .= sprintf($format, $src, $params);
				}
			}
		}
		return $html;
	}
	
	/**
	 * Method minifies .css and .js files.
	 * (Custom method not from original block.)
	 *
	 * @param string $format
	 * @param string $mergedUrl
	 * @param string $params
	 * @return string
	 */
	public function minifyCssJs($format, $mergedUrl, $params)
	{
	
		$relativeUrl = strstr($mergedUrl, '/media/');
	
		$baseUrl = str_replace($relativeUrl, '', $mergedUrl);
	
		$relativeUrlPathArray = explode('/', $relativeUrl);
		$relativePath = implode(DS, $relativeUrlPathArray);
	
		$originalFile = array_pop($relativeUrlPathArray);
	
		$baseDir = Mage::getBaseDir();
	
		$originalFileRealPath = $baseDir . $relativePath;
	
		// CSS
		if (strpos($format, '<link') === 0) {
				
			if (!Mage::getStoreConfigFlag('apptrian_minify/general/minify_css')) {
				return $mergedUrl;
			}
				
			$minifiedFilename     = $this->getMinifiedFilename($originalFile, 'css');
			$minifiedFileRealPath = $this->getMinifiedFileRealPath($baseDir, $minifiedFilename, $relativeUrlPathArray);
			$minifiedFileUrl      = $this->getMinifiedFileUrl($baseUrl, $minifiedFilename, $relativeUrlPathArray);
				
			if (!file_exists($minifiedFileRealPath)) {
				
				if (file_put_contents($minifiedFileRealPath, Minify::combine($originalFileRealPath)) === false) {
					
					Mage::log('Minified CSS file could not be written.');
					
					$minifiedFileUrl = $mergedUrl;
					
				}
	
			}
				
		}
	
		// JS
		if (strpos($format, '<script') === 0) {
				
			if (!Mage::getStoreConfigFlag('apptrian_minify/general/minify_js')) {
				return $mergedUrl;
			}
				
			$minifiedFilename     = $this->getMinifiedFilename($originalFile, 'js');
			$minifiedFileRealPath = $this->getMinifiedFileRealPath($baseDir, $minifiedFilename, $relativeUrlPathArray);
			$minifiedFileUrl      = $this->getMinifiedFileUrl($baseUrl, $minifiedFilename, $relativeUrlPathArray);
				
			if (!file_exists($minifiedFileRealPath)) {
				
				if (file_put_contents($minifiedFileRealPath, Minify::combine($originalFileRealPath)) === false) {
						
					Mage::log('Minified JS file could not be written.');
						
					$minifiedFileUrl = $mergedUrl;
						
				}
	
			}
				
		}
	
		return $minifiedFileUrl;
	
	}
	
	/**
	 * Generates filename of minified file.
	 * (Custom method not from original block.)
	 *
	 * @param string $file
	 * @param string $type
	 * @return string
	 */
	public function getMinifiedFilename($file, $type)
	{
		return hash('md5', $file . 'apptrian_minify') . '.' . $type;
	}
	
	/**
	 * Returns real path of minified file.
	 * (Custom method not from original block.)
	 *
	 * @param string $baseDir
	 * @param string $minifiedFilename
	 * @param array $relativeUrlPathArray
	 * @return string
	 */
	public function getMinifiedFileRealPath($baseDir, $minifiedFilename, $relativeUrlPathArray)
	{
		$relativePath = implode(DS, $relativeUrlPathArray);
		return $baseDir . $relativePath . DS . $minifiedFilename;
	}
	
	/**
	 * Returns url of minified file.
	 * (Custom method not from original block.)
	 *
	 * @param string $baseUrl
	 * @param string $minifiedFilename
	 * @param array $relativeUrlPathArray
	 * @return string
	 */
	public function getMinifiedFileUrl($baseUrl, $minifiedFilename, $relativeUrlPathArray)
	{
		$relativeUrl = implode('/', $relativeUrlPathArray);
		return $baseUrl . $relativeUrl . '/' . $minifiedFilename;
	}
	
}
