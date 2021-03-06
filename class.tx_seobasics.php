<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2007-2011 Benjamin Mack <benni@typo3.org>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/** 
 * @author		Benjamin Mack (benni@typo3.org) 
 * @subpackage	tx_seobasics
 * 
 * This package includes all hook implementations.
 */

class tx_seobasics {

	/**
	 * returns the URL for the current webpage
	 */
	public function getCanonicalUrl($content, $conf) {
		if ($GLOBALS['TSFE']->page['tx_seo_canonicaltag']) {
			$url = $GLOBALS['TSFE']->page['tx_seo_canonicaltag'];
		} else {
			$configuration = array(
				'parameter' => $GLOBALS['TSFE']->id . ',' . $GLOBALS['TSFE']->type,
				'addQueryString' => 1,
				'addQueryString.' => array(
					'method' => 'GET'
				),
				'forceAbsoluteUrl' => 1
			);
			$url = $GLOBALS['TSFE']->cObj->typoLink_URL($configuration);
			$url = $GLOBALS['TSFE']->baseUrlWrap($url);
		}

		if ($url) {
			$urlParts = parse_url($url);
			$scheme = $urlParts['scheme'];
			if (isset($conf['useDomain'])) {
				if ($conf['useDomain'] == 'current') {
					$domain = t3lib_div::getIndpEnv('HTTP_HOST');
				} else {
					$domain = $conf['useDomain'];
				}
				if (!$scheme) {
					$scheme = 'http';
				}
    			$url =  $scheme . '://' . $domain . $urlParts['path']; 
			} elseif (!$urlParts['scheme']) {
				$pageWithDomains = $GLOBALS['TSFE']->findDomainRecord();
				// get first domain record of that page
				$allDomains = $GLOBALS['TSFE']->sys_page->getRecordsByField(
					'sys_domain',
					'pid', $pageWithDomains,
					'AND redirectTo = ""' . $GLOBALS['TSFE']->sys_page->enableFields('sys_domain'),
					'',
					'sorting ASC'
				);
				if (count($allDomains)) {
					$domain = (t3lib_div::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://');
					$domain = $domain . $allDomains[0]['domainName'];
					$domain = rtrim($domain, '/') . '/' . t3lib_div::getIndpEnv('TYPO3_SITE_PATH');
				} else {
					$domain = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
				}
				$url = rtrim($domain, '/') . '/' . ltrim($url, '/');
			}
				// remove everything after the ?
			list($url, ) = explode('?', $url);
		}
		return $url;
	}




	/**
	 * Hook function for cleaning output XHTML
	 * hooks on "class.tslib_fe.php:2946"
	 *
	 * @param       array           hook parameters
	 * @param       object          Reference to parent object (TSFE-obj)
	 * @return      void
	 */
	public function processOutputHook(&$feObj, $ref) {
		if ($GLOBALS['TSFE']->type != 0) {
			return;
		}
		$spltContent = explode("\n", $ref->content);
		$level = 0;

		$cleanContent = array();
		$textareaOpen = false;
		foreach ($spltContent as $lineNum => $line)	{
			$line = trim($line);
			if (empty($line)) continue;
			$out = $line;

			// ugly strpos => TODO: use regular expressions
			// starts with an ending tag
			if (strpos($line, '</div>') === 0
			|| (strpos($line, '<div')   !== 0 && strpos($line, '</div>') === strlen($line)-6)
			|| strpos($line, '</html>') === 0
			|| strpos($line, '</body>') === 0
			|| strpos($line, '</head>') === 0
			|| strpos($line, '</ul>') === 0)
				$level--;


			if (strpos($line, '<textarea') !== false) {
				$textareaOpen = true;
			}

				// add indention only if no textarea is open 
			if (!$textareaOpen) {
				for ($i = 0; $i < $level; $i++)	{
					$out = "\t".$out;
				}
			}

			if (strpos($line, '</textarea>') !== false) {
				$textareaOpen = false;
			}

			// starts with an opening <div>, <ul>, <head> or <body>
			if ((strpos($line, '<div') === 0 && strpos($line, '</div>')  !== strlen($line)-6)
			|| (strpos($line, '<body') === 0 && strpos($line, '</body>') !== strlen($line)-7)
			|| (strpos($line, '<head') === 0 && strpos($line, '</head>') !== strlen($line)-7)
			|| (strpos($line, '<ul')   === 0 && strpos($line, '</ul>')   !== strlen($line)-5))
				$level++;


			$cleanContent[] = $out;
		}

		$ref->content = implode("\n", $cleanContent);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seo_basics/class.tx_seobasics.php']) {
   include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/seo_basics/class.tx_seobasics.php']);
}
?>
