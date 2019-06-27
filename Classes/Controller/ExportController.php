<?php

namespace Archriss\ArcExport\Controller;

/* * ************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Christophe Monard <cmonard@archriss.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * Class ExportController
 *
 * @author  Christophe Monard
 * @package arc_export
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ExportController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    protected static $defaultContentType = 'text/x-csv';
    protected static $defaultCharset = 'ISO-8859-1';
    protected static $reservedSettings = array('defaultExportTemplate', 'defaultDatetimeFormat');

    /**
     * Current Page ID
     *
     * @var integer
     */
    protected $pageId = 0;

    /**
     * Store namespace to class model
     *
     * @var array
     */
    protected $classes = array();

    /**
     * @var \TYPO3\CMS\Core\Resource\Index\FileIndexRepository
     */
    protected $fileIndexRepository;

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceStorage
     */
    protected $storage;

    /**
     * fluidRenderer
     *
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $fluidRenderer;

    /**
     * Initialize controller
     *
     * @return void
     */
    protected function initializeAction()
    {
        $this->pageId = $this->objectManager->get(\FluidTYPO3\Flux\Configuration\BackendConfigurationManager::class)->getCurrentPageId();
        $dataMapper = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        foreach ($this->settings as $namespace => $conf) {
            if (!in_array($namespace, self::$reservedSettings) && class_exists($namespace)) {
                $tableName = $dataMapper->convertClassNameToTableName($namespace);
                if ($tableName != '') {
                    $this->classes[$tableName][] = array(
                        'title' => $conf['name'],
                        'namespace' => $namespace,
                        'inThisPage' => false,
                    );
                }
            }
        }
    }

    /**
     * main action
     *
     * @return void
     */
    public function listTablesAction()
    {
        $this->view->assign('menu', $this->getMenu());
        $this->view->assign('classes', $this->classes);
        $this->view->assign('isAdmin', $GLOBALS['BE_USER']->user['admin']);
    }

    /**
     * Initialize summary action
     * Instantiate FAL mechanics
     */
    public function initializeSummaryTableAction()
    {
        $this->initializeFAL();
    }

    /**
     * Summary view
     *
     * @param string $namespace
     * @param string $download
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     */
    public function summaryTableAction($namespace, $download = null)
    {
        // Get repository name
        $repository = $this->settings[$namespace]['repository'] ? $this->settings[$namespace]['repository'] : str_replace('\\Model\\', '\\Repository\\', $namespace) . 'Repository';
        // Instantiate Repository
        $pluginRepository = $this->objectManager->get($repository);
        $countAll = $pluginRepository->countByPid($this->pageId);
        $exportedField = $this->settings[$namespace]['exportField'] ? $this->settings[$namespace]['exportField'] : null;
        if (!is_null($exportedField)) {
            $methodName = 'countBy' . ucfirst($exportedField);
            if (method_exists($pluginRepository, $methodName)) {
                $countExported = $pluginRepository->{$methodName}($this->pageId, 0);
            } else {
                $countExported = -1;
            }
        }
        // Folder
        $folderName = $this->settings[$namespace]['subfolder'] && $this->settings[$namespace]['subfolder'] != '' ? $this->settings[$namespace]['subfolder'] : null;
        $usePidFolder = $this->settings[$namespace]['usePidSubFolder'] && $this->settings[$namespace]['usePidSubFolder'] == 1 ? true : false;
        $folder = $this->getFolder($folderName, $usePidFolder);
        // Assign values
        $this->view->assignMultiple(array(
            'menu' => $this->getMenu(),
            'title' => $this->settings[$namespace]['name'],
            'namespace' => $namespace,
            'exported' => $exportedField,
            'countAll' => $countAll,
            'countExported' => $countExported,
            'files' => $this->storage->getFilesInFolder($folder, 0, 0, true, false, 'tstamp', true),
            'download' => $download,
        ));
    }

    /**
     * Initialize export action
     * Instantiate FAL mechanics
     */
    public function initializeExportAction()
    {
        $this->initializeFAL();
    }

    /**
     * Export CSV
     *
     * @param string $namespace
     * @param boolean $onlyNonExported
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFileWritePermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function exportAction($namespace, $onlyNonExported = false)
    {
        foreach ($this->classes as $table => $config) {
            if ($config['namespace'] == $namespace) {
                break;
            }
        }
        // Get repository name
        $repository = $this->settings[$namespace]['repository'] ? $this->settings[$namespace]['repository'] : str_replace('\\Model\\', '\\Repository\\', $namespace) . 'Repository';
        // Instantiate Repository
        $pluginRepository = $this->objectManager->get($repository);
        $exportedField = $this->settings[$namespace]['exportField'] ? $this->settings[$namespace]['exportField'] : null;
        if ($onlyNonExported && !is_null($exportedField)) {
            $exportedField = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($exportedField);
            $methodName = 'findBy' . $exportedField;
            if (method_exists($pluginRepository, $methodName)) {
                $rows = $pluginRepository->{$methodName}($this->pageId, 0);
                if ($rows) {
                    $method = 'set' . $exportedField;
                    foreach ($rows as $row) {
                        $row->{$method}(true);
                        $pluginRepository->update($row);
                    }
                }
            } else {
                $rows = null;
            }
        } else {
            $rows = $pluginRepository->findByPid($this->pageId);
        }
        // Assign to template
        $this->initializeFluidTemplate($namespace);
        $this->fluidRenderer->assignMultiple(array(
            'rows' => $rows,
            'table' => $table,
            'tcaFields' => \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['interface']['showRecordFieldList'], true),
            'localSettings' => $this->settings[$namespace]['settings'],
        ));
        // File part
        $fileName = $this->storeFile($this->settings[$namespace]);
        $this->redirect('summaryTable', null, null, array('namespace' => $namespace, 'download' => $fileName));
    }

    /**
     * Initialize get file action
     * Instantiate FAL mechanics
     */
    public function initializeGetFileAction()
    {
        $this->initializeFAL();
    }

    /**
     * Get file download
     *
     * @param string $namespace
     * @param string $file
     */
    public function getFileAction($namespace, $file)
    {
        $file = $this->storage->getFile($file);
        // Handle header values
        $contentType = $this->settings[$namespace]['contentType'] && $this->settings[$namespace]['contentType'] != '' ? $this->settings[$namespace]['contentType'] : static::$defaultContentType;
        $charset = $this->settings[$namespace]['charset'] && $this->settings[$namespace]['charset'] != '' ? $this->settings[$namespace]['charset'] : static::$defaultCharset;
        // Throw headers
        header('Content-Type: ' . $contentType . '; charset=' . $charset);
        header('Content-length:' . $file->getSize());
        header('Content-Disposition: attachment; filename="' . $file->getName() . '"');
        header('Pragma: no-cache');
        echo $file->getContents();
        exit;
    }

    /**
     * Initialize FluidRenderer
     *
     * @param string $namespace
     */
    protected function initializeFluidTemplate($namespace)
    {
        // Get renderer
        $this->fluidRenderer = $this->objectManager->get(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        // Search all paths
        $fluidTemplate = $this->settings[$namespace]['template'] ? $this->settings[$namespace]['template'] : $this->settings['defaultExportTemplate'];
        list($localBaseTemplatePath,) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('Templates', $this->settings['defaultExportTemplate']);
        list($baseTemplatePath,) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('Templates', $fluidTemplate);
        // Select good template
        $fluidTemplate = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($fluidTemplate);
        list(, $format) = \TYPO3\CMS\Core\Utility\GeneralUtility::revExplode('.', $fluidTemplate, 2);
        // Set partials and layouts paths
        $this->fluidRenderer->setPartialRootPaths(array(
            0 => $localBaseTemplatePath . 'Partials/',
            10 => $baseTemplatePath . 'Partials/',
        ));
        $this->fluidRenderer->setLayoutRootPaths(array(
            0 => $localBaseTemplatePath . 'Layouts/',
            10 => $baseTemplatePath . 'Layouts/',
        ));
        // Initialize templates and paths
        $this->fluidRenderer->setTemplatePathAndFilename($fluidTemplate);
        $this->fluidRenderer->setFormat($format);
    }

    /**
     * Initialize FAL system
     */
    protected function initializeFAL()
    {
        // Init du filestorage
        $uploadDirectory = 'uploads/tx_arcexport/';
        $storageRepository = $this->objectManager->get(\TYPO3\CMS\Core\Resource\StorageRepository::class);
        $storages = $storageRepository->findAll();
        foreach ($storages as $storage) {
            $storageRecord = $storage->getStorageRecord();
            $configuration = $storage->getConfiguration();
            $isLocalDriver = $storageRecord['driver'] === 'Local';
            $isOnUpload = !empty($configuration['basePath']) && \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($configuration['basePath'], $uploadDirectory);
            if ($isLocalDriver && $isOnUpload) {
                $this->storage = $storage;
                break;
            }
        }
        if (!isset($this->storage)) {
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException('Unable to initialize storage folder for export.', 1535314568);
        }
        $this->fileIndexRepository = $this->objectManager->get(\TYPO3\CMS\Core\Resource\Index\FileIndexRepository::class);
    }

    /**
     * Get folder
     *
     * @param mixed $folderName
     * @param boolean $usePidFolder
     * @return \TYPO3\CMS\Core\Resource\Folder
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     */
    protected function getFolder($folderName = null, $usePidFolder = false)
    {
        if (!is_null($folderName)) {
            if (!$this->storage->hasFolder($folderName)) {
                $this->storage->createFolder($folderName);
            }
            $folder = $this->storage->getFolder($folderName);
        } else {
            $folder = $this->storage->getRootLevelFolder();
        }
        if ($usePidFolder) {
            if (!$this->storage->hasFolderInFolder('pid-' . $this->pageId, $folder)) {
                $this->storage->createFolder('pid-' . $this->pageId, $folder);
            }
            $folder = $this->storage->getFolderInFolder('pid-' . $this->pageId, $folder);
        }
        return $folder;
    }

    /**
     * Store file to the desired folder
     *
     * @param array $localSettings
     * @return string Filename to use
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\IllegalFileExtensionException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFileWritePermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException
     */
    protected function storeFile($localSettings)
    {
        // set acces to storage
        $this->storage->setEvaluatePermissions(false);
        /* Filename */
        $fileName = $localSettings['filenameTmpl'] && $localSettings['filenameTmpl'] != '' ? $localSettings['filenameTmpl'] : 'export-{datehour}.csv';
        // prepare export file
        if (strpos($fileName, '{') !== false) { // if at least 1 { is found, proceed to replace in filename
            // current datetime
            if (strpos($fileName, '{datehour}') !== false) {
                $dateHourFormat = $localSettings['dateHourFormat'] && $localSettings['dateHourFormat'] != '' ? $localSettings['dateHourFormat'] : '%y%m%d%H%M';
                $fileName = str_replace('{datehour}', strftime($dateHourFormat), $fileName);
            }
        }
        $basicFileUtility = $this->objectManager->get(\TYPO3\CMS\Core\Resource\Driver\LocalDriver::class);
        $fileName = $basicFileUtility->sanitizeFileName($fileName);
        /* Folder */
        $folderName = $localSettings['subfolder'] && $localSettings['subfolder'] != '' ? $localSettings['subfolder'] : null;
        $usePidFolder = $localSettings['usePidSubFolder'] && $localSettings['usePidSubFolder'] == 1 ? true : false;
        $folder = $this->getFolder($folderName, $usePidFolder);
        /* store file */
        $this->storage->setFileContents($this->storage->createFile($fileName, $folder), $this->fluidRenderer->render());
        // set restore acces to storage
        $this->storage->setEvaluatePermissions(true);
        // return filename
        return $fileName;
    }

    /**
     * Function returning menu items
     * TODO : improve detection using domain/repository
     */
    protected function getMenu()
    {
        $menu = array();
        if (count($this->classes)) {
            foreach ($this->classes as $table => $items) {
                foreach ($items as $key => $item) {
                    // Get repository name
                    $repository = $this->settings[$item['namespace']]['repository'] ? $this->settings[$item['namespace']]['repository'] : str_replace('\\Model\\', '\\Repository\\', $item['namespace']) . 'Repository';
                    if (class_exists($repository)) {
                        // Instantiate Repository
                        $pluginRepository = $this->objectManager->get($repository);
                        $count = $pluginRepository->countByPid($this->pageId);
                        if ($count) {
                            $this->classes[$table][$key]['inThisPage'] = true;
                            if (!array_key_exists($table, $menu)) {
                                $menu[$table] = array();
                            }
                            $menu[$table][] = $item;
                        }
                    }
                }
            }
        }
        return $menu;
    }

}
