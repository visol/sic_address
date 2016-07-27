<?php
namespace SICOR\SicAddress\Domain\Repository;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 SICOR DEVTEAM <dev@sicor-kdl.net>, Sicor KDL GmbH
 *
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
 ***************************************************************/

/**
 * The repository for Addresses
 */
class AddressRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    
    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'sorting' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
    );

    /**
     * @return array
     */
    public function getDefaultOrderings()
    {
        return $this->defaultOrderings;
    }

    /**
     * @param array $defaultOrderings
     */
    public function setDefaultOrderings(array $defaultOrderings)
    {
        $this->defaultOrderings = $defaultOrderings;
    }

    public function initializeObject() {
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        $querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * @param string $sql
     */
    public function runUpdate($sql) {
        $query = $this->createQuery();
        $query->statement($sql);
        return $query->execute();
    }

    /**
     * @return array
     */
    public function findAtoz($field, $addresstable, $categories)
    {
        $where = "deleted=0 AND pid<>-1 AND hidden=0 ";
        if ($categories && count($categories > 0)) {
            $where .= "AND (";
            foreach ($categories as $category) {
                $where .= "uid IN (SELECT uid_foreign FROM sys_category_record_mm WHERE uid_local='".$category->getUid()."' AND sys_category_record_mm.tablenames = 'tt_address' AND sys_category_record_mm.fieldname = 'categories') OR ";
            }
            $where .= "1=0 )";
        }

        $res = array();
        $results = $GLOBALS['TYPO3_DB']->exec_SELECTquery('DISTINCT UPPER(LEFT('.$field.', 1)) as letter', $addresstable, $where);
        while($result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($results))	{
            $res[] = $result['letter'];
        }
        return $res;
    }

    /**
     * @return array
     */
    public function search($atozvalue, $atozField, $categories, $queryvalue, $queryField) {

        $query = $this->createQuery();

        $constraints = array();
        if ($atozField && !($atozField === "none") && $atozvalue && strlen(trim($atozvalue)) === 1)
            $constraints[] = $query->like($atozField, $atozvalue.'%');
        if ($queryField && !($queryField === "none") && $queryvalue && !($queryvalue === ""))
            $constraints[] = $query->like($queryField, '%'.$queryvalue.'%');

        if ($categories && count($categories > 0))
        {
            $catconstraints = array();
            foreach ($categories as $category) {
                $catconstraints[] = $query->contains("categories", $category->getUid());
            }

            $constraints[] = $query->logicalOr($catconstraints);
        }

        if(count($constraints) < 1)
            return $this->findAll();

        $query->matching($query->logicalAnd($constraints));
        return $query->execute();
    }
}