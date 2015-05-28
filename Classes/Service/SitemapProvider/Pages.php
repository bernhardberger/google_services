<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Tim Lochmüller
 *  			
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
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
 * ************************************************************* */

/**
 * Description of Pages
 *
 * @author timlochmueller
 */
class Tx_GoogleServices_Service_SitemapProvider_Pages implements Tx_GoogleServices_Interface_SitemapProviderInterface {

	/**
	 * Get the Records
	 *
	 * @param integer $startPage
	 * @param array $basePages
	 * @param Tx_GoogleServices_Controller_SitemapController $obj
	 *
	 * @return Tx_GoogleServices_Domain_Model_Node
	 */
	public function getRecords($startPage, $basePages, Tx_GoogleServices_Controller_SitemapController $obj) {
		$nodes = array();

		foreach ($basePages as $uid) {
			// Build URL
			$url = $obj->getUriBuilder()
				->setTargetPageUid($uid)
				->build();

			// can't generate a valid url
			if (!strlen($url)) {
				continue;
			}

			// Get Record
			$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $uid);

			// exclude Doctypes
			if (in_array($record['doktype'], array(
				3,
				4
			))) {
				continue;
			}

			// Build Node
			$node = new Tx_GoogleServices_Domain_Model_Node();
			$node->setLoc($url);
			$node->setPriority($this->getPriority($startPage, $record));
			$node->setChangefreq(Tx_GoogleServices_Service_SitemapDataService::mapTimeout2Period($record['cache_timeout']));
			$node->setLastmod($this->getModifiedDate($record));

			#$geo = new Tx_GoogleServices_Domain_Model_Node_Geo();
			#$geo->setFormat('kml');
			#$node->setGeo($geo);

			$nodes[] = $node;
		}

		return $nodes;
	}

	/**
	 * Get the priority
	 *
	 * @param integer $startPage
	 * @param array   $record
	 *
	 * @return float
	 */
	protected function getPriority($startPage, $record) {
		$nodePrio = (float)$record['node_priority'];
		if ($nodePrio > 0) {
			return $nodePrio;
		}

		// Prio
		$rootline = $GLOBALS['TSFE']->sys_page->getRootLine($record['uid']);
		$find = FALSE;
		foreach ($rootline as $key => $value) {
			if ($find) {
				unset($rootline[$key]);
			}
			if ($value['uid'] == $startPage) {
				$find = TRUE;
			}
		}

		switch (sizeof($rootline)) {
			case 1:
				return 1.0;
			case 2:
				return 0.9;
			case 3:
				return 0.9;
			case 4:
				return 0.7;
			default:
				return 0.5;
		}
	}

	/**
	 * get the modifiedDate
	 *
	 * @param array $record
	 *
	 * @return integer
	 */
	protected function getModifiedDate($record) {
		// Last mod
		$lastMod = $record['crdate'];
		if ($record['tstamp'] > $lastMod) {
			$lastMod = $record['tstamp'];
		}
		if ($record['SYS_LASTCHANGED'] > $lastMod) {
			$lastMod = $record['SYS_LASTCHANGED'];
		}
		return $lastMod;
	}

}