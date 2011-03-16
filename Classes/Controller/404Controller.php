<?php
declare(encoding = 'utf-8');
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Michiel Roos <extenstions@typofree.org>
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
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * The 404 controller for the 404 sponsoring package
 *
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class tx_Sponsoring404_Controller_404Controller {

	/**
	 *
	 * The extension configuration
	 * @var array
	 */
	private $settings;

	/**
	 *
	 * Array with language labels
	 * @var array
	 */
	private $languageLabels;

	/**
	 * main function that serves as the entry point from TYPO3
	 *
	 * @param		array		The content array.
	 * @param		array		The conf array.
	 * @return		string		HTML-representation of data.
	 */
	public function main($content, $settings) {
		$languageKey = $GLOBALS['TSFE']->config['config']['language'] ? $GLOBALS['TSFE']->config['config']['language'] : 'default';
		if ($languageKey == 'default') {
			$this->languageLabels = t3lib_div::readLLfile(t3lib_extMgm::extPath('sponsoring404') . 'Resources/Private/Language/locallang.xml', $languageKey, $GLOBALS['TSFE']->renderCharset);
		} else {
			$this->languageLabels = t3lib_div::readLLfile(t3lib_extMgm::extPath('sponsoring404') . 'Resources/Private/Language/' . $languageKey . '.locallang.xml', $languageKey, $GLOBALS['TSFE']->renderCharset);
		}

		if (!count($settings)) {
			return $this->languageLabels['default']['staticTSNotLoaded'];
		}
		$this->settings =  $settings['view.'];

		if (!trim($this->settings['charity'])) {
			return $this->languageLabels['default']['charityNotConfigured'];
		}
		$numberOfCharities = count($this->settings['charities.']);
		if ($numberOfCharities > 1) {
			if ($this->settings['charity'] == 'Random') {
				$randomCharity = array_rand($this->settings['charities.']);
				$directory = $this->settings['charities.'][$randomCharity]['directory'];
			} else {
				foreach ($this->settings['charities.'] as $charity) {
					if ($this->settings['charity'] == $charity['name']) {
						$directory = $charity['directory'];
						break;
					}
				}
			}
		} else {
			return $this->languageLabels['default']['charitiesNotAvailable'];
		}
		$relativePath = $this->getDirectoryName($this->settings['templateRootPath']) . $directory;
		$aboslutePath = PATH_site . $relativePath;

		if (@is_file($aboslutePath . '/index.html')) {
			$file = file($aboslutePath . '/index.html');
			$prefix = str_replace(t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT'), '', PATH_site);
			$content = join('', str_replace('###DIR404PAGES###', $prefix . $relativePath, $file));
		} else {
			return $this->languageLabels['default']['charityNotFound'];
		}
		return $content;
	}

	/**
	 * adapted from: class.t3lib_tstemplate->getFileName()
	 *
	 * Returns the reference to a 'resource' in TypoScript.
	 * This could be from the filesystem if '/' is found in the value $directoryFromSetup, else from the resource-list
	 *
	 * @param	string		TypoScript "resource" data type value.
	 * @return	string		Resulting directoryname, if any.
	 */
	private function getDirectoryName ($directoryFromSettings) {
			// Sets the paths from where TypoScript resources are allowed to be used:
		$allowedPaths = Array(
			'media/',
			$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'],	// fileadmin/ path
			'uploads/',
			'typo3temp/',
			't3lib/fonts/',
			TYPO3_mainDir . 'ext/',
			TYPO3_mainDir . 'sysext/',
			TYPO3_mainDir . 'contrib/',
			'typo3conf/ext/'
		);

		$directory = trim($directoryFromSettings);
		if (!$directory) {
			return;
		} elseif (strstr($directory,'../')) {
			if ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('Directory path "'.$directory.'" contained illegal string "../"!',3);
			return;
		}

		if (!strcmp(substr($directory, 0, 4), 'EXT:')) {
			$newDirectory='';
			list($extKey, $script) = explode('/', substr($directory, 4), 2);
			if ($extKey && t3lib_extMgm::isLoaded($extKey)) {
				$extPath = t3lib_extMgm::extPath($extKey);
				$newDirectory = substr($extPath, strlen(PATH_site)) . $script;
			}
			if (!@is_dir(PATH_site.$newDirectory)) {
				if ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('Extension media directory "'.$newDirectory.'" was not found!',3);
				return;
			} else $directory = $newDirectory;
		}

			// find
		if (strpos($directory, '/') !== false) {
			// if the file is in the media/ folder but it doesn't exist,
				// it is assumed that it's in the tslib folder
			if (t3lib_div::isFirstPartOfStr($directory, 'media/') && !is_file($this->getFileName_backPath . $directory)) {
				$directory = t3lib_extMgm::siteRelPath('cms') . 'tslib/' . $directory;
			}
			if (is_dir($this->getFileName_backPath . $directory)) {
				$outDirectory = $directory;
				$fileInfo = t3lib_div::split_fileref($outDirectory);
				$OK = 0;
				foreach ($allowedPaths as $val) {
					if (substr($fileInfo['path'], 0, strlen($val)) == $val) {
						$OK = 1;
						break;
					}
				}
				if ($OK) {
					return $outDirectory;
				} elseif ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('"' . $directory . '" was not located in the allowed paths: (' . implode(',', $this->allowedPaths) . ')', 3);
			} elseif ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('"' . $this->getFileName_backPath . $directory . '" is not a directory (non-uploads/.. resource, did not exist).', 3);
		} else { // Here it is uploaded media:
			$outDirectory = $this->extractFromResources($this->setup['resources'], $directory);
			if ($outDirectory) {
			 	if (@is_dir($this->uplPath.$outDirectory))	{
					$this->fileCache[$hash] = $this->uplPath.$outDirectory;
					return $this->uplPath.$outDirectory;
				} elseif ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('"'.$this->uplPath . $outDirectory . '" is not a file (did not exist).',3);
			} elseif ($this->tt_track) $GLOBALS['TT']->setTSlogMessage('"' . $directory . '" is not a directory (uploads/.. resource).',3);
		}
	}
}
?>