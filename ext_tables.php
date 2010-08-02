<?php
declare(encoding = 'utf-8');
if (!defined ('TYPO3_MODE')) die ('Access denied.');

	// Add an entry in the static template list found in sys_templates
t3lib_extMgm::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Default TS');

?>