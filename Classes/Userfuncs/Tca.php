<?php /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
/** @noinspection PhpFullyQualifiedNameUsageInspection */

/** @noinspection PhpUnusedParameterInspection */
namespace Thucke\ThRating\Userfuncs;

use Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Thucke\ThRating\Domain\Model\Ratingobject;
use Thucke\ThRating\Domain\Model\Syslang;
use Thucke\ThRating\Domain\Model\Stepconf;
use Thucke\ThRating\Domain\Repository\StepconfRepository;
use Thucke\ThRating\Domain\Repository\StepnameRepository;
use Thucke\ThRating\Utility\DeprecationHelperUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Thomas Hucke <thucke@web.de>
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
***************************************************************/

/**
 * The backend helper function class
 *
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tca
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Constructs a new rating object
     */
    public function __construct()
    {
        if (empty($this->objectManager)) {
            $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        }
    }

    /**
     * Returns the record title for the rating object in BE
     * Note that values of $params are modified by reference
     *
     * @param $params
     * @param $pObj
     */
    public function getRatingObjectRecordTitle(&$params, &$pObj)
    {
        $params['title'] =
            '#' . $params['row']['uid'] . ': ' . $params['row']['ratetable'] . ' [' . $params['row']['ratefield'] . ']';
    }

    /**
     * Returns the record title for the step configuration in BE
     * Note that values of $params are modified by reference
     *
     * @param $params
     * @param $pObj
     */
    public function getStepconfRecordTitle(&$params, &$pObj)
    {
        $params['title'] = '#' . $params['row']['uid'] . ': Steporder [' . $params['row']['steporder'] . ']';
    }

    /**
     * Returns the record title for the step configuration name in BE
     * Note that values of $params are modified by reference
     *
     * @param $params
     * @param $pObj
     */
    public function getStepnameRecordTitle(&$params, &$pObj)
    {
        //look into repository to find clear text object attributes
        $stepnameRepository = $this->objectManager->get(StepnameRepository::class);
        $stepnameRepository->clearQuerySettings(); //disable syslanguage and enableFields
        $stepnameObject = $stepnameRepository->findByUid((int)($params['row']['uid']));
        /** @var string $stepnameLang */
        /** @var string $sysLang */
        $syslang = '';
        if (is_object($stepnameObject)) {
            /** @var \Thucke\ThRating\Domain\Model\Stepname $stepnameObject */
            $stepnameLang = $stepnameObject->get_languageUid();
            if (empty($stepnameLang)) {
                $syslang = LocalizationUtility::translate('tca.BE.default', 'ThRating');
            } elseif ($stepnameLang === -1) {
                $syslang = LocalizationUtility::translate('tca.BE.all', 'ThRating');
            } else {
                //look for language name
                $syslangRepository = $this->objectManager->get(StepnameRepository::class);
                $syslangObject = $syslangRepository->findByUid($stepnameLang);
                if ($syslangObject instanceof Syslang) {
                    $syslang = $syslangObject->getTitle();
                } else {
                    $syslang = LocalizationUtility::translate('tca.BE.unknown', 'ThRating');
                }
            }
        }
        $stepconfRepository = $this->objectManager->get(StepconfRepository::class);
        $stepconfObject = $stepconfRepository->findByUid((int)($params['row']['stepconf']));
        $ratetable = LocalizationUtility::translate('tca.BE.new', 'ThRating');
        $ratefield = LocalizationUtility::translate('tca.BE.new', 'ThRating');
        $steporder = LocalizationUtility::translate('tca.BE.new', 'ThRating');
        if ($stepconfObject instanceof Stepconf) {
            $ratingObject = $stepconfObject->getRatingobject();
            if ($ratingObject instanceof LazyLoadingProxy) {
                $ratingObject = $ratingObject->_loadRealInstance();
            }
            if ($ratingObject instanceof Ratingobject) {
                $ratetable = $ratingObject->getRatetable();
                $ratefield = $ratingObject->getRatefield();
                $steporder = $stepconfObject->getSteporder();
            }
        }
        $params['title'] = $ratetable . '[' . $ratefield . ']/Step ' . $steporder . '/' . $syslang;
    }

    /**
     * Returns the record title for the rating in BE
     * Note that values of $params are modified by reference
     *
     * @param $params
     * @param $pObj
     */
    public function getRatingRecordTitle(&$params, &$pObj)
    {
        $params['title'] = '#' . $params['row']['uid'] . ': RowUid [' . $params['row']['ratedobjectuid'] . ']';
    }

    /**
     * Returns the record title for the rating in BE
     * Note that values of $params are modified by reference
     *
     * @param $params
     * @param $pObj
     */
    public function getVoteRecordTitle(&$params, &$pObj)
    {
        $params['title'] = 'Voteuser Uid [' . $params['row']['voter'] . ']';
    }

    /**
     * Returns all configured ratinglink display types for flexform
     *
     * @param array $config
     * @return  array ratinglink configurations
     * @throws Exception
     */
    /** @noinspection PhpUnused */
    public function dynFlexRatinglinkConfig($config)
    {
        //\TYPO3\CMS\Core\Utility\DebugUtility::debug($config,'config');
        $flexFormPid = $config['flexParentDatabaseRow']['pid'];
        $settings = $this->loadTypoScriptForBEModule('tx_thrating', $flexFormPid);
        $ratingconfigs = $settings['settings.']['ratingConfigurations.'];

        $optionList = [];

        // add first option - Default
        $optionList[0] = [0 => 'Default', 1 => ''];
        foreach ($ratingconfigs as $configKey => $configValue) {
            $lastDot = strrpos($configKey, '.');
            if ($lastDot) {
                $name = substr($configKey, 0, $lastDot);
                // add option
                $optionList[] = [0 => $name, 1 => $name];
            }
        }
        /** @noinspection AdditionOperationOnArraysInspection */
        $config['items'] += $optionList;

        return $config;
    }

    /**
     * Loads the TypoScript for the given extension prefix, e.g. tx_cspuppyfunctions_pi1, for use in a backend module.
     *
     * @param string $extKey Extension key to look for config
     * @param int $pid pageUid
     * @return  array
     * @throws Exception
     */
    public function loadTypoScriptForBEModule($extKey, $pid)
    {
        $rootLine = DeprecationHelperUtility::getRootLine($pid);
        $TSObj = $this->objectManager->get(ExtendedTemplateService::class);
        $TSObj->tt_track = 0;
        $TSObj->init();
        $TSObj->runThroughTemplates($rootLine);
        $TSObj->generateConfig();

        return $TSObj->setup['plugin.'][$extKey . '.'];
    }
}
