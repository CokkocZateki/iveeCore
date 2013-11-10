<?php

/**
 * SDEUtil offers a series of helper functions, mostly for looking up default values and handling string formatting
 *
 * @author aineko-m <Aineko Macx @ EVE Online>
 * @license https://github.com/aineko-m/iveeCore/blob/master/LICENSE
 * @link https://github.com/aineko-m/iveeCore/blob/master/SDEUtil.php
 * @package iveeCore
 */
class SDEUtil {
    
    /**
     * @var float $posSlotCost the estimated cost of a POS slot per second
     */
    protected static $posSlotCost = null;
    
    /**
     * Returns the skill level.
     * This is a stub implementation. Extend as required.
     * @param int $skillID the ID of the skill being looked up
     * @return int skill level
     */
    public static function getSkillLevel($skillID){
        //stub
        return 5;
    }
    
    /**
     * Sanity checks a skill level (verify it's an integer between 0 and 5)
     * @param int $skillLevel the value to be checked
     * @return bool true on success
     * @throws InvalidParameterValueException if $skillLevel is not a valid skill level
     */
    public static function sanityCheckSkillLevel($skillLevel){
        if($skillLevel < 0 OR $skillLevel > 5 OR $skillLevel%1 > 0)
            throw new InvalidParameterValueException("Skill level needs to be an integer between 0 and 5");
        return true;
    }
    
    /**
     * Returns the blueprint ME level.
     * This is a stub implementation. Extend as required.
     * @param int $bpID the ID of the blueprint being looked up
     * @return int blueprint ME level
     */
    public static function getBpMeLevel($bpID){
        //stub
        $meData = array(
            23758 => 2, //Archon BP
            23920 => 2, //Aeon BP
            11568 => 2, //Avatar BP
            23912 => 2, //Thanatos BP
            23914 => 2, //Nyx BP
            1002  => 2, //Erebus BP
            23916 => 2, //Chimera BP
            23918 => 2, //Wyvern BP
            3765  => 2, //Leviathan BP
            24484 => 2, //Nidhoggur BP
            22853 => 2, //Hel BP
            23774 => 2, //Regnarok BP
            19721 => 2, //Revelation BP
            19725 => 2, //Moros BP
            19727 => 2, //Phoenix BP
            19723 => 2, //Naglfar BP
            28353 => 2, //Rorqual BP
            28607 => 2, //Orca BP
            20184 => 2, //Providence BP
            20188 => 2, //Obelisk BP
            20186 => 2, //Charon BP
            20190 => 2, //Fenrir BP
        );
        if(isset($meData[$bpID]))
            return $meData[$bpID];
        else
            return iveeCoreConfig::getDefaultBpoMe();
    }
    
    /**
     * Returns the blueprint PE level.
     * This is a stub implementation. Extend as required.
     * @param int $bpID the ID of the blueprint being looked up
     * @return int blueprint PE level
     */
    public static function getBpPeLevel($bpID){
        //Stub
        $peData = array(
            23758 => 1, //Archon BP
            23920 => 1, //Aeon BP
            11568 => 1, //Avatar BP
            23912 => 1, //Thanatos BP
            23914 => 1, //Nyx BP
            1002  => 1, //Erebus BP
            23916 => 1, //Chimera BP
            23918 => 1, //Wyvern BP
            3765  => 1, //Leviathan BP
            24484 => 1, //Nidhoggur BP
            22853 => 1, //Hel BP
            23774 => 1, //Regnarok BP
            19721 => 1, //Revelation BP
            19725 => 1, //Moros BP
            19727 => 1, //Phoenix BP
            19723 => 1, //Naglfar BP
            28353 => 1, //Rorqual BP
            28607 => 1, //Orca BP
            20184 => 1, //Providence BP
            20188 => 1, //Obelisk BP
            20186 => 1, //Charon BP
            20190 => 1, //Fenrir BP
        );
        if(isset($peData[$bpID]))
            return $peData[$bpID];
        else
            return iveeCoreConfig::getDefaultBpoPe();
    }
    
    /**
     * Calulcates the effective reprocessing (= refining) yield
     * @param float $standings with the corporation of the station you are reprocessing at
     * @param float $stationRefineryEffiency of the station you are reprocessing at
     * @param int $refiningSkillLevel. If left null, it is looked up.
     * @param int $refineryEfficiencySkillLevel. If left null it is looked up.
     * @param int $specificRefiningSkillLevel item-specific reprocessing skill level (or refining or scrap metal processing)
     * @param float $implantMod implant reprocessing yield bonus, as factor (1.0 >= x <= 1.05)
     * @return int blueprint PE level
     * @throws InvalidParameterValueException if invalid parameter values are given
     */
    public static function calcReprocessingFactor(
            $standings = 6.67, 
            $stationRefineryEffiency = 0.5, 
            $refiningSkillLevel = null, 
            $refineryEfficiencySkillLevel = null, 
            $specificRefiningSkillLevel = 0, 
            $implantMod = 1.0){
        
        //sanity checks
        if($standings < 0 OR $standings > 10) 
            throw new InvalidParameterValueException("Standing needs to be between 0.0 and 10.0");
        if($stationRefineryEffiency < 0.3 OR $stationRefineryEffiency > 0.5) 
            throw new InvalidParameterValueException("Station refinery efficiency needs to be >=0.3 and <= 0.5");
        if($implantMod < 1.0 OR $implantMod > 1.05) 
            throw new InvalidParameterValueException("Implant modifier needs to be >= 1.0 and <= 1.05");
        
        //set undefined skill levels, sanity check otherwise
        $utilClass = iveeCoreConfig::getIveeClassName('util');
        if(is_null($refiningSkillLevel))
            $refiningSkillLevel = $utilClass::getSkillLevel(3385);
        else
            $utilClass::sanityCheckSkillLevel($refiningSkillLevel);

        if(is_null($refineryEfficiencySkillLevel))
            $refineryEfficiencySkillLevel = $utilClass::getSkillLevel(3389);
        else
            $utilClass::sanityCheckSkillLevel($refineryEfficiencySkillLevel);
        
        $utilClass::sanityCheckSkillLevel($specificRefiningSkillLevel);

        //calculate reprocessing yield
        $reprocessingYield = $stationRefineryEffiency 
        + 0.375 
            * (1 + $refiningSkillLevel * 0.02) 
            * (1 + $refineryEfficiencySkillLevel * 0.04) 
            * (1 + $specificRefiningSkillLevel * 0.05)
        + ($implantMod - 1);
        //clamp to 1
        if($reprocessingYield > 1.0) $reprocessingYield = 1.0;
        
        //calculate tax factor
        $tax = 0.05 - (0.075 * $standings);
        if($tax < 0) $tax = 0;
        
        return $reprocessingYield * (1 - $tax);
    }
    
    /**
     * Returns a hipothetical POS slots cost assuming the configured hourly fuel materials, number of active slots and
     * use factor.
     * @return float the estimated cost per POS slot per second
     */
    public static function getPosSlotCostPerSecond() {
        //avoid calculating the cost twice
        if (isset(self::$posSlotCost))
            return self::$posSlotCost;

        //get number of configured POS slots
        $numSlots = 
            iveeCoreConfig::getUsePosManufacturing() ? iveeCoreConfig::getNumManufactureSlots() : 0
            + iveeCoreConfig::getUsePosCopying()     ? iveeCoreConfig::getNumCopySlots() : 0
            + iveeCoreConfig::getUsePosInvention()   ? iveeCoreConfig::getNumInventionSlots() : 0
            + iveeCoreConfig::getUsePosMeResearch()  ? iveeCoreConfig::getNumMeResearchSlots() : 0
            + iveeCoreConfig::getUsePosPeResearch()  ? iveeCoreConfig::getNumPeResearchSlots() : 0;
        if($numSlots < 1){
            self::$posSlotCost = 0;
            return self::$posSlotCost;
        }
        
        //IDs and quantities of items consumed hourly
        $hourlyMaterials = iveeCoreConfig::getHourlyMaterials();
        
        //sum hourly fuel material costs
        $hourlySum = 0;
        foreach ($hourlyMaterials as $typeID => $amount) {
            $type = SDE::instance()->getType($typeID);
            if ($type instanceof Manufacturable)
                //we build manufacturable materials
                $hourlySum += $type->getBlueprint()->manufacture($amount)->getTotalCost();
            else
                //we buy non-manufacturables
                $hourlySum += $amount * $type->getBuyPrice() * iveeCoreConfig::getDefaultBuyTaxFactor();
        }
        //destribute cost over all configured slots, account for slot underutilization
        self::$posSlotCost = $hourlySum / ($numSlots * 3600) / iveeCoreConfig::getPosSlotUtilizationFactor();
        return self::$posSlotCost;
    }
    
    /**
     * Converts long numbers to nice readable representation with appended unit: K, M or G
     * @param int|float $val the number to be formatted
     * @return string the formated number
     */
    public static function quantitiesToReadable($val) {
        if (abs($val) < 1000) {
            if ($val - ((int) $val) !== 0)
                return sprintf("%1.2f", $val);
            else
                return $val;
        } elseif (abs($val) >= 1000000000) {
            $val = $val / 1000000000;
            $unit = 'G';
        } elseif (abs($val) >= 1000000) {
            $val = $val / 1000000;
            $unit = 'M';
        } else {
            $val = $val / 1000;
            $unit = 'K';
        }
        return sprintf("%1.2f", $val) . $unit;
    }
    
    /**
     * Convenience function for converting large second values into a 1d2h33m44s representation
     * @param int $fseconds the seconds to be formatted
     * @return string the formated time
     */
    public static function secondsToReadable($fseconds) {
        $seconds = (int) $fseconds;
        if (($fseconds - $seconds) * 60 > 1)
            $seconds++;

        $readable = "";
        if ($seconds >= (24 * 3600)) {
            $readable .= (int) ($seconds / (24 * 60 * 60)) . "d ";
            $seconds = $seconds % (24 * 60 * 60);
            return $readable . (int) ($seconds / (60 * 60)) . "h";
        }
        if ($seconds >= 3600) {
            $readable .= (int) ($seconds / (60 * 60)) . "h ";
            $seconds = $seconds % (60 * 60);
            return $readable . (int) ($seconds / 60) . "m";
        }
        if ($seconds >= 60) {
            $readable .= (int) ($seconds / 60) . "m ";
            $seconds = $seconds % 60;
        }
        $readable .= $seconds . "s";

        return $readable;
    } 
    
    /**
     * Makes INSERT .. ON DUPLICATE KEY UPDATE query string
     * @param string $table the name of the SQL table to be used
     * @param array $insert the data to be inserted as column => value
     * @param array $update the data to be updated as column => value, optional
     * @return string the SQL query
     */
    public static function makeUpsertQuery($table, &$insert, &$update = NULL){
        //prepare columns and values list
        $icols   = "";
        $ivalues = "";
        foreach($insert as $i => $val){
            if(!isset($val)) continue;
            $icols   .= ", `".$i."`";
            $ivalues .= ", ".$val;
        }

        $icols   = substr($icols, 2);
        $ivalues = substr($ivalues, 2);

        $q = "INSERT INTO ".$table." (".$icols.") VALUES (".$ivalues.")";

        if(is_array($update)){
            $us = "";
            foreach($update as $u => $val){
                if(!isset($val)) continue;
                $us .= ", `".$u."` = ".$val;
            }
            $q .= PHP_EOL . "ON DUPLICATE KEY UPDATE ".substr($us, 2);
        } 
        return $q.";" . PHP_EOL;
    }
}

?>