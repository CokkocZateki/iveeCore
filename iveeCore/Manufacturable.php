<?php
/**
 * Manufacturable class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Manufacturable.php
 *
 */

namespace iveeCore;

/**
 * Class for all Types that can be manufactured
 * Inheritance: Manufacturable -> Sellable -> Type.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/Manufacturable.php
 *
 */
class Manufacturable extends Sellable
{
    /**
     * @var int $producedFromBlueprintID the of the blueprint this item can be manufactured from
     */
    protected $producedFromBlueprintID;

    /**
     * Gets all necessary data from SQL
     * 
     * @return array
     * @throws \iveCore\Exceptions\TypeIdNotFoundException when a typeID is not found
     */
    protected function queryAttributes()
    {
        $sdeClass = Config::getIveeClassName('SDE');
        $row = $sdeClass::instance()->query(
            "SELECT
            it.groupID,
            ig.categoryID,
            it.typeName,
            it.volume,
            it.portionSize,
            it.basePrice,
            it.marketGroupID,
            iap.typeID as blueprintTypeID
            FROM invTypes AS it
            JOIN industryActivityProducts as iap ON iap.productTypeID = it.typeID
            JOIN invGroups AS ig ON it.groupID = ig.groupID
            WHERE it.published = 1
            AND iap.activityID = 1
            AND it.typeID = " . (int) $this->typeID . ";"
        )->fetch_assoc();

        if (empty($row)) {
            $exceptionClass = Config::getIveeClassName('TypeIdNotFoundException');
            throw new $exceptionClass("typeID ". (int) $this->typeID . " not found");
        }
        return $row;
    }

    /**
     * Sets attributes from SQL result row to object
     * 
     * @param array $row data from DB
     * 
     * @return void
     */
    protected function setAttributes(array $row)
    {
        parent::setAttributes($row);
        if (isset($row['blueprintTypeID']))
            $this->producedFromBlueprintID = (int) $row['blueprintTypeID'];
    }

    /**
     * Returns blueprint object that can manufacture this item
     * 
     * @return Blueprint
     */
    public function getBlueprint()
    {
        $typeClass = Config::getIveeClassName('Type');
        return $typeClass::getType($this->producedFromBlueprintID);
    }
}