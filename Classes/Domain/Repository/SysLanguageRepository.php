<?php

/*
 * This file is part of the TemplaVoilà project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

namespace Schnitzler\Templavoila\Domain\Repository;

use Schnitzler\Templavoila\Traits\BackendUser;
use Schnitzler\Templavoila\Traits\DatabaseConnection;

/**
 * Class Schnitzler\Templavoila\Domain\Repository\SysLanguageRepository
 */
class SysLanguageRepository
{
    use BackendUser;
    use DatabaseConnection;

    /**
     * @param array $where
     *
     * @return array
     */
    protected function addExcludeHiddenWhereClause(array $where = [])
    {
        if (!static::getBackendUser()->isAdmin()) {
            $where[] = 'sys_language.hidden = 0';
        }

        return $where;
    }

    /**
     * @param bool $excludeHidden
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function findAll()
    {
        $whereClause = '1=1';
        $where = $this->addExcludeHiddenWhereClause();

        if (count($where) > 0) {
            $whereClause .= ' and ' . implode(' and ', $where);
        }

        return (array) static::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'sys_language',
            $whereClause,
            '',
            'sys_language.uid',
            '',
            'uid'
        );
    }

    /**
     * @param int $pid
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function findAllForPid($pid)
    {
        if ($pid < 1) {
            throw new \InvalidArgumentException(
                'Param $uid must be greater than zero',
                1466505308
            );
        }

        $where = [
            'pages_language_overlay.sys_language_uid = sys_language.uid',
            'pages_language_overlay.pid = ' . $pid,
            'pages_language_overlay.deleted = 0'
        ];

        $where = $this->addExcludeHiddenWhereClause($where);
        $whereClause = '1=1 and ' . implode(' and ', $where);

        return (array) static::getDatabaseConnection()->exec_SELECTgetRows(
            'DISTINCT sys_language.*',
            'pages_language_overlay,sys_language',
            $whereClause,
            '',
            'sys_language.title'
        );
    }

    /**
     * @param int $pid
     * @return array
     */
    public function findAllForPossiblePageTranslations($pid)
    {
        $where = [
            'pages_language_overlay.uid is null'
        ];
        $where = $this->addExcludeHiddenWhereClause($where);
        $whereClause = '1=1 and ' . implode(' and ', $where);

        return (array)static::getDatabaseConnection()->exec_SELECTgetRows(
            'sys_language.*',
            'sys_language left join pages_language_overlay on sys_language.uid = pages_language_overlay.sys_language_uid'
            . ' and pages_language_overlay.pid = ' . $pid,
            $whereClause,
            '',
            '',
            '',
            'uid'
        );
    }
}
