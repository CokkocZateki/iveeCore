<?php
/**
 * InventorBlueprint class file.
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventorBlueprint.php
 *
 */

namespace iveeCore;

/**
 * Class for blueprints that can be used for inventing.
 * Inheritance: InventorBlueprint -> Blueprint -> Sellable -> Type.
 *
 * @category IveeCore
 * @package  IveeCoreClasses
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/iveeCore/InventorBlueprint.php
 *
 */
class InventorBlueprint extends Blueprint
{
    /**
     * @var array $inventsBlueprintID holds the inventable blueprint ID(s)
     */
    protected $inventsBlueprintIDsChance = array();

    /**
     * @var int $decryptorGroupID groupID of compatible decryptors
     */
    protected $decryptorGroupID;

    /**
     * @var int $encryptionSkillID the relevant decryptor skillID
     */
    protected $encryptionSkillID;

    /**
     * @var array $datacoreSkillIDs the relevant datacore skillIDs
     */
    protected $datacoreSkillIDs;

    /**
     * Constructor. Use \iveeCore\Type::getType() to instantiate InventorBlueprint objects instead.
     * 
     * @param int $typeID of the InventorBlueprint object
     * 
     * @return \iveeCore\InventorBlueprint
     * @throws \iveeCore\Exceptions\TypeIdNotFoundException if typeID is not found
     */
    protected function __construct($typeID)
    {
        parent::__construct($typeID);
        $sdeClass = Config::getIveeClassName('SDE');
        $sde = $sdeClass::instance();

        //query for inventable blueprints, probability and decryptorGroupID
        $res = $sde->query(
            "SELECT iap.productTypeID, iap.probability, COALESCE(valueInt, valueFloat) as decryptorGroupID
            FROM dgmTypeAttributes as dta
            JOIN industryActivityMaterials as iam ON iam.materialTypeID = dta.typeID
            JOIN industryActivityProbabilities as iap ON iap.typeID = iam.typeID
            WHERE attributeID = 1115
            AND iam.activityID = 8
            AND iap.activityID = 8
            AND iam.typeID = " . (int) $this->typeID . ';'
        );

        if ($res->num_rows < 1) {
            $exceptionClass = Config::getIveeClassName('TypeIdNotFoundException');
            throw new $exceptionClass("Inventor data for blueprintID=" . (int) $this->typeID ." not found");
        }

        while ($row = $res->fetch_assoc()) {
            $this->inventsBlueprintIDsChance[(int) $row['productTypeID']] = (float) $row['probability'];
            $this->decryptorGroupID = (int) $row['decryptorGroupID'];
        }

        //get the mapping for skills to datacore or interface
        $res = $sde->query(
            "SELECT COALESCE(valueInt, valueFloat) as skillID, it.groupID
            FROM dgmTypeAttributes as dta
            JOIN invTypes as it ON it.typeID = dta.typeID
            WHERE dta.attributeID = 182
            AND groupID IN (333, 716)
            AND COALESCE(valueInt, valueFloat) IN ("
            . implode(', ', array_keys($this->getSkillMapForActivity(ProcessData::ACTIVITY_INVENTING)->getSkills()))
            . ");"
        );
        $this->datacoreSkillIDs = array();
        while ($row = $res->fetch_assoc()) {
            if ($row['groupID'] == 333)
                $this->datacoreSkillIDs[] = $row['skillID'];
            elseif ($row['groupID'] == 716)
                $this->encryptionSkillID = $row['skillID'];
        }
    }

    /**
     * Returns an InventionProcessData object describing the invention process.
     * 
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpID the ID if the blueprint to be invented. If left null, it is set to the first 
     * inventable blueprint ID
     * @param int $decryptorID the decryptor the be used, if any
     * @param boolean $recursive defines if manufacturables should be build recursively
     * 
     * @return \iveeCore\InventionProcessData
     * @throws \iveeCore\Exceptions\NotInventableException if the specified blueprint can't be invented from this
     * @throws \iveeCore\Exceptions\InvalidParameterValueException if inputBPCRuns exceeds limit
     * @throws \iveeCore\Exceptions\WrongTypeException if decryptorID isn't a decryptor
     * @throws \iveeCore\Exceptions\InvalidDecryptorGroupException if a non-matching decryptor is specified
     */
    public function invent(IndustryModifier $iMod, $inventedBpID = null, $decryptorID = null, $recursive = true)
    {
        $inventionDataClass = Config::getIveeClassName('InventionProcessData');
        $typeClass = Config::getIveeClassName('Type');
        $inventableBpIDs = $this->getInventableBlueprintIDs();

        //if no inventedBpID given, set to first inventable BP ID
         if (is_null($inventedBpID))
             $inventedBpID = $inventableBpIDs[0];

        //check if the given BP can be invented from this
        elseif (!isset($this->inventsBlueprintIDsChance[$inventedBpID])) {
            $exceptionClass = Config::getIveeClassName('NotInventableException');
            throw new $exceptionClass("Specified blueprint can't be invented from this inventor blueprint.");
        }

        //get invented BP
        $inventedBp = $typeClass::getType($inventedBpID);

        //get modifiers and test if inventing is possible with the given assemblyLines
        $modifier = $iMod->getModifier(ProcessData::ACTIVITY_INVENTING, $inventedBp->getProduct());

        //calculate base cost, its the average of all possible invented BP's product base cost
        $baseCost = 0;
        foreach ($inventableBpIDs as $inventableBpID)
            $baseCost += $typeClass::getType($inventableBpID)->getProductBaseCost();
        $baseCost = $baseCost / count($inventableBpIDs);

        //with decryptor
        if ($decryptorID > 0) {
            $decryptor = $this->getAndCheckDecryptor($decryptorID);
            $id = new $inventionDataClass(
                $inventedBpID,
                $this->getBaseTimeForActivity(ProcessData::ACTIVITY_INVENTING) * $modifier['t'],
                $baseCost * 0.02 * $modifier['c'],
                $this->calcInventionChance($inventedBpID) * $decryptor->getProbabilityModifier(),
                $inventedBp->getMaxProductionLimit() + $decryptor->getRunModifier(),
                0 - $decryptor->getMEModifier(),
                0 - $decryptor->getTEModifier(),
                $modifier['solarSystemID'],
                $modifier['assemblyLineTypeID'],
                isset($modifier['teamID']) ? $modifier['teamID'] : null
            );
            $id->addMaterial($decryptorID, 1);
        } else { //without decryptor
            $id = new $inventionDataClass(
                $inventedBpID,
                $this->getBaseTimeForActivity(ProcessData::ACTIVITY_INVENTING) * $modifier['t'],
                $baseCost * 0.02 * $modifier['c'],
                $this->calcInventionChance($inventedBpID),
                $inventedBp->getMaxProductionLimit(),
                0,
                0,
                $modifier['solarSystemID'],
                $modifier['assemblyLineTypeID'],
                isset($modifier['teamID']) ? $modifier['teamID'] : null
            );
        }
        $id->addSkillMap($this->getSkillMapForActivity(ProcessData::ACTIVITY_INVENTING));

        foreach ($this->getMaterialsForActivity(ProcessData::ACTIVITY_INVENTING) as $matID => $matData) {
            $mat = $typeClass::getType($matID);

            //calculate total quantity needed, applying all modifiers
            $totalNeeded = ceil($matData['q'] * $modifier['m']);

            //if consume flag is set to 0, add to needed mats with quantity 0
            if (isset($matData['c']) and $matData['c'] == 0) {
                $id->addMaterial($matID, 0);
                continue;
            }

            //if using recursive building and material is manufacturable, recurse!
            if ($recursive AND $mat instanceof Manufacturable) {
                $id->addSubProcessData($mat->getBlueprint()->manufacture($iMod, $totalNeeded));
            } else {
                $id->addMaterial($matID, $totalNeeded);
            }
        }
        return $id;
    }

    /**
     * For a given typeID, checks if its a compatible decryptor and returns a Decryptor object
     * 
     * @param int $decryptorID the decryptorID to be checked
     * 
     * @return \iveeCore\Decryptor
     * @throws \iveeCore\Exceptions\WrongTypeException if $decryptorID does not reference a Decryptor
     * @throws \iveeCore\Exceptions\InvalidDecryptorGroupException if the Decryptor is not compatible with Blueprint
     */
    protected function getAndCheckDecryptor($decryptorID)
    {
        $typeClass = Config::getIveeClassName('Type');
        $decryptor = $typeClass::getType($decryptorID);

        //check if decryptorID is actually a decryptor
        if (!($decryptor instanceof Decryptor)) {
            $exceptionClass = Config::getIveeClassName('WrongTypeException');
            throw new $exceptionClass('typeID ' . $decryptorID . ' is not a Decryptor');
        }

        //check if decryptor group matches blueprint
        if ($decryptor->getGroupID() != $this->decryptorGroupID) {
            $exceptionClass = Config::getIveeClassName('InvalidDecryptorGroupException');
            throw new $exceptionClass('Given decryptor does not match blueprint race');
        }
        return $decryptor;
    }

    /**
     * Copy, invent T2 blueprint and manufacture from it in one go
     * 
     * @param IndustryModifier $iMod the object with all the necessary industry modifying entities
     * @param int $inventedBpID the ID of the blueprint to be invented. If left null it will default to the first 
     * blueprint defined in inventsBlueprintID
     * @param int $decryptorID the decryptor the be used, if any
     * @param bool $recursive defines if manufacturables should be build recursively
     * 
     * @return ManufactureProcessData with cascaded InventionProcessData and CopyProcessData objects
     */
    public function copyInventManufacture(IndustryModifier $iMod, $inventedBpID = null, $decryptorID = null,
        $recursive = true
    ) {
        //make one BP copy
        $copyData = $this->copy($iMod, 1, 1, $recursive);

        //run the invention
        $inventionData = $this->invent(
            $iMod,
            $inventedBpID,
            $decryptorID,
            $recursive
        );

        //add copyData to invention data
        $inventionData->addSubProcessData($copyData);

        //manufacture from invented BP
        $manufactureData = $inventionData->getProducedType()->manufacture(
            $iMod,
            $inventionData->getResultRuns(),
            $inventionData->getResultME(),
            $inventionData->getResultTE(),
            $recursive
        );

        //add invention data to the manufactureProcessData object
        $manufactureData->addSubProcessData($inventionData);

        return $manufactureData;
    }

    /**
     * Returns an array with the IDs of inventable blueprints
     * 
     * @return array
     */
    public function getInventableBlueprintIDs()
    {
        return array_keys($this->inventsBlueprintIDsChance);
    }

    /**
     * Returns an array with the IDs of compatible decryptors
     * 
     * @return array
     */
    public function getDecryptorIDs()
    {
        return Decryptor::getIDsFromGroup($this->decryptorGroupID);
    }

    /**
     * Calculates the invention chance
     * 
     * @param int $inventedBpID the ID of the InvetableBlueprint to check
     * @param int $metaLevel the metalevel of the optional input item
     * 
     * @return float
     */
    public function calcInventionChance($inventedBpID, $metaLevel = 0)
    {
        if (!isset($this->inventsBlueprintIDsChance[$inventedBpID])) {
            $exceptionClass = Config::getIveeClassName('TypeIdNotFoundException');
            throw new $exceptionClass("The given blueprint cannot be invented from blueprintID=" . $this->getTypeID());
        }
        $defaultsClass = Config::getIveeClassName('Defaults');
        $defaults = $defaultsClass::instance();

        return $this->inventsBlueprintIDsChance[$inventedBpID]
            * (1 + 0.01 * $defaults->getSkillLevel($this->encryptionSkillID))
            * (1 + ($defaults->getSkillLevel($this->datacoreSkillIDs[0])
                + $defaults->getSkillLevel($this->datacoreSkillIDs[1]))
            * (0.1 / (5 - $metaLevel)));
    }
}