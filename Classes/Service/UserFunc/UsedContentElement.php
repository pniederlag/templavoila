<?php
namespace Extension\Templavoila\Service\UserFunc;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Finding used content elements on pages. Used as a filter for other extensions
 * which wants to know which elements are used on a templavoila page.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class UsedContentElement
{

    /**
     * @var array
     */
    public $usedUids = [];

    /**
     * Initialize object with page id.
     *
     * @param int $page_uid UID of page in processing
     */
    public function init($page_uid)
    {

        // Initialize TemplaVoila API class:
        $apiObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Extension\Templavoila\Service\ApiService::class, 'pages');

        // Fetch the content structure of page:
        $contentTreeData = $apiObj->getContentTree('pages', \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordRaw('pages', 'uid=' . (int)$page_uid));
        if ($contentTreeData['tree']['ds_is_found']) {
            $this->usedUids = array_keys($contentTreeData['contentElementUsage']);
            $this->usedUids[] = 0;
        }
    }

    /**
     * Returns TRUE if either table is NOT tt_content OR (in case it is tt_content) if the uid is in the built page.
     *
     * @param string $table
     * @param int $uid
     *
     * @return bool
     */
    public function filter($table, $uid)
    {
        if ($table != 'tt_content' || in_array($uid, $this->usedUids)) {
            return true;
        }

        return false;
    }
}
