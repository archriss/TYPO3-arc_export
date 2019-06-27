<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'Archriss.' . $_EXTKEY,
    'web',
    'export',
    '',
    array(
        'Export' => 'listTables, summaryTable, export, getFile'
    ),
    array(
        'access' => 'user,group',
        'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/export-icon.png',
        'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
    )
);
