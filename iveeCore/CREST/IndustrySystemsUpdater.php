<?php
/**
 * IndustrySystemsUpdater class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustrySystemsUpdater.php
 *
 */

namespace iveeCore\CREST;

/**
 * IndustrySystemsUpdater specific CREST data updater
 *
 * @category IveeCore
 * @package  IveeCoreCrest
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/CREST/IndustrySystemsUpdater.php
 *
 */
class IndustrySystemsUpdater extends CrestDataUpdater
{
    /**
     * @var string $path holds the CREST path
     */
    protected static $path = 'industry/systems/';
    
    /**
     * @var string $representationName holds the expected representation name returned by CREST
     */
    protected static $representationName = 'vnd.ccp.eve.IndustrySystemCollection-v1';

    /**
     * Processes data objects to SQL
     * 
     * @param \stdClass $item to be processed
     *
     * @return string the SQL queries
     */
    protected function processDataItemToSQL(\stdClass $item)
    {
        $exceptionClass = \iveeCore\Config::getIveeClassName('CrestException');

        if (!isset($item->solarSystem->id))
            throw new $exceptionClass('systemID missing in Industry Systems CREST data');
        $systemID = (int) $item->solarSystem->id;

        $update = array();

        foreach ($item->systemCostIndices as $indexObj) {
            if (!isset($indexObj->activityID))
                throw new $exceptionClass(
                    'activityID missing in Industry Systems CREST data for systemID ' . $systemID);
            if (!isset($indexObj->costIndex))
                throw new $exceptionClass(
                    'costIndex missing in Industry Systems CREST data for systemID ' . $systemID);

            switch ($indexObj->activityID) {
            case 1:
                $update['manufacturingIndex'] = (float) $indexObj->costIndex;
                break;
            case 3:
                $update['teResearchIndex'] = (float) $indexObj->costIndex;
                break;
            case 4:
                $update['meResearchIndex'] = (float) $indexObj->costIndex;
                break;
            case 5:
                $update['copyIndex'] = (float) $indexObj->costIndex;
                break;
            case 7:
                $update['reverseIndex'] = (float) $indexObj->costIndex;
                break;
            case 8:
                $update['inventionIndex'] = (float) $indexObj->costIndex;
                break;
            default :
                throw new $exceptionClass(
                    'Unknown activityID received from Industry Systems CREST data for systemID ' . $systemID);
            }
        }
        $insert = $update;
        $insert['systemID'] = $systemID;
        $insert['date'] = date('Y-m-d');

        $this->updatedIDs[] = $systemID;

        $sdeClass = \iveeCore\Config::getIveeClassName('SDE');

        return $sdeClass::makeUpsertQuery('iveeIndustrySystems', $insert, $update);
    }

    /**
     * Invalidate any cache entries that were update in the DB
     *
     * @return void
     */
    protected function invalidateCaches()
    {
        $assemblyLineClass  = \iveeCore\Config::getIveeClassName('SolarSystem');
        $assemblyLineClass::getInstancePool()->deleteFromCache($this->updatedIDs);
    }
}
