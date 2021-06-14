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

namespace Schnitzler\Templavoila\Update;

use TYPO3\CMS\Dbal\Database\DatabaseConnection as DbalDatabaseConnection;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Class Schnitzler\Templavoila\Update\DataStructureDataprotMigrationWizard
 */
class DataStructureDataprotMigrationWizard extends AbstractUpdate
{
    const TABLE = 'tx_templavoila_datastructure';

    public function __construct()
    {
        $this->title = 'Migrate data structures.';
    }

    /**
     * @param string &$description The description for the update
     * @return bool
     */
    public function checkForUpdate(&$description)
    {
        $description .= 'This script uses the algorithm of <a href="https://github.com/mogic-le/templavoila-76-fixer" target="_blank">https://github.com/mogic-le/templavoila-76-fixer</a> to fix your data strcutures.' . '<br /><br />';
        $description .= '!!! Backup <em>' . static::TABLE . '</em> before running this wizard !!!';

        $databaseConnection = $this->getDatabaseConnection();

        if ($databaseConnection instanceof DbalDatabaseConnection) {
            return false;
        }

        $count = (int)$databaseConnection->exec_SELECTcountRows('uid', static::TABLE);

        return $count > 0;
    }

    /**
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        $rows = (array)$this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid,dataprot',
            static::TABLE,
            ''
        );

        foreach ($rows as $row) {
            $uid = (int)$row['uid'];

            try {
                $xmlElement = new \SimpleXMLElement($row['dataprot']);

                if (!$xmlElement instanceof \SimpleXMLElement) {
                    continue;
                }

                // move <type> inside <tx_templavoila>
                $elems = $xmlElement->xpath('//*[tx_templavoila and type and not(section)]');
                foreach ($elems as $elem) {
                    $elem->tx_templavoila->type = $elem->type;
                    unset($elem->type);
                }

                // add <title> ourside <tx_templavoila> for containers
                // first only for containers within sections
                $elems = $xmlElement->xpath('//*[@type="array" and tx_templavoila/title and not(title) and ../../section="1"]');
                foreach ($elems as $elem) {
                    $elem->title = $elem->tx_templavoila->title;
                }

                // fix browse_links.php
                foreach ($xmlElement->xpath('//link[script]') as $elem) {
                    $script = (string)$elem->script;

                    if ($script === 'browse_links.php?mode=wizard') {
                        unset($elem->script);
                        $m = $elem->addChild('module');
                        $m['type'] = 'array';
                        $m->addChild('name', 'wizard_element_browser');
                        $up = $m->addChild('urlParameters');
                        $up['type'] = 'array';
                        $up->addChild('mode', 'wizard');
                    }
                }

                // format XML as it was before
                // echo $xmlElement->asXML();
                $dom = new \DOMDocument('1.0');
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                $dom->loadXML($xmlElement->asXML());
                $xml = $dom->saveXML(null, LIBXML_NOEMPTYTAG);

                $this->getDatabaseConnection()->exec_UPDATEquery(
                    static::TABLE,
                    'uid = ' . $uid,
                    [
                        'dataprot' => preg_replace_callback(
                            '#^( )+#m',
                            function ($matches) {
                                return str_repeat("\t", strlen($matches[0]) / 2);
                            },
                            $xml
                        )
                    ]
                );
            } catch (\Exception $e) {
                $customMessages .= $e->getMessage() . PHP_EOL;
                continue;
            }
        }

        return true;
    }
}
