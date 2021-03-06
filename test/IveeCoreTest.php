<?php
/**
 * iveeCore PHPUnit testfile
 *
 * PHP version 5.3
 *
 * @category IveeCore
 * @package  IveeCoreTests
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/test/IveeCoreTest.php
 *
 */

error_reporting(E_ALL);
ini_set('display_errors', 'on');

//include the iveeCore configuration, expected in the iveeCore directory, with absolute path
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'iveeCoreInit.php');

/**
 * PHPUnit test for iveeCore
 *
 * The tests cover different parts of iveeCore and focus on the trickier cases. It is mainly used to aid during the
 * development, but can also be used to check the correct working of an iveeCore installation.
 *
 * To run this test, you'll need to have PHPUnit isntalled as well as created the iveeCoreConfig.php file based on the
 * provided template.
 *
 * @category IveeCore
 * @package  IveeCoreExtensions
 * @author   Aineko Macx <ai@sknop.net>
 * @license  https://github.com/aineko-m/iveeCore/blob/master/LICENSE GNU Lesser General Public License
 * @link     https://github.com/aineko-m/iveeCore/blob/master/test/IveeCoreTest.php
 */
class IveeCoreTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (\iveeCore\Config::getUseCache())
            \iveeCore\MemcachedWrapper::instance()->flushCache();
    }
    
    public function testSde()
    {
        $this->assertTrue(\iveeCore\SDE::instance() instanceof \iveeCore\SDE);
    }

    public function testBasicTypeMethods()
    {
        $type = \iveeCore\Type::getById(22);
        $this->assertTrue($type instanceof \iveeCore\Sellable);
        $this->assertTrue($type->getId() == 22);
        $this->assertTrue($type->getGroupID() == 450);
        $this->assertTrue($type->getCategoryID() == 25);
        $this->assertTrue($type->getName() == 'Arkonor');
        $this->assertTrue($type->getVolume() == 16);
        $this->assertTrue($type->getPortionSize() == 100);
        $this->assertTrue($type->getBasePrice() == 2174386.0);
        $this->assertTrue($type->isReprocessable());
        $this->assertTrue(is_array($type->getMaterials()));
    }
    
    public function testGetTypeAndCache()
    {
        //can't test cache with cache disabled
        if (!\iveeCore\Config::getUseCache())
            return;
        
        //empty cache entry for type
        \iveeCore\MemcachedWrapper::instance()->deleteItem('type_' . 645);
        
        //get type
        $type = \iveeCore\Type::getById(645);
        $this->assertTrue($type instanceof \iveeCore\Manufacturable);
        $this->assertTrue($type == \iveeCore\MemcachedWrapper::instance()->getItem('type_' . 645));
        $this->assertTrue($type == \iveeCore\Type::getByName('Dominix'));
    }
    
    public function testBasicBlueprintMethods()
    {
        $type = \iveeCore\Type::getById(2047); //DC I Blueprint
        $this->assertTrue($type instanceof \iveeCore\Blueprint);
        //stubs
        $type->getProduct();
        $type->getMaxProductionLimit();
        $type->getProductBaseCost(0);
        $this->assertTrue($type->calcResearchMultiplier(0, 2) * 105 == 250);
        
        $iMod = \iveeCore\IndustryModifier::getBySystemIdForPos(30000119);
        $type->manufacture($iMod);
        $type->copy($iMod);
        $type->invent($iMod);
        $type->researchME($iMod, 0, 10);
        $type->researchTE($iMod, 0, 20);
        
        $type = \iveeCore\Type::getById(22431); //Sin Blueprint
        $type->copyInventManufacture($iMod);

    }

    public function testAssemblyLine()
    {
        //test supercap modifiers on supercap assembly array
        $assLine = \iveeCore\AssemblyLine::getById(10);
        $type = \iveeCore\Type::getById(23919);
        $this->assertTrue(array('t' => 1, 'm' => 1, 'c' => 1) == $assLine->getModifiersForType($type));

        //test battleship modifiers on large ship assembly array
        $type = \iveeCore\Type::getById(645);
        $assLine = \iveeCore\AssemblyLine::getById(155);
        $this->assertTrue(array('t' => 0.75, 'm' => 0.98, 'c' => 1) == $assLine->getModifiersForType($type));
    }

    /**
     * Test that a supercapital cannot be built in a Capital Ship Assembly Array
     * @expectedException \iveeCore\Exceptions\TypeNotCompatibleException
     */
    public function testAssemblyLineException()
    {
        $assLine = \iveeCore\AssemblyLine::getById(21);
        $type = \iveeCore\Type::getById(23919);
        $assLine->getModifiersForType($type);
    }

public function testManufacturing()
    {
//        //Dominix - Test if extra materials are handled correctly when PE skill level < 5
//        $mpd = \iveeCore\Type::getById(645)->getBlueprint()->manufacture(1, 10, 5, false, 4);
//        $this->assertTrue($mpd->getProducedType()->getId() == 645);
//        $this->assertTrue($mpd->getTime() == 12000);
//        $materialTarget = new \iveeCore\MaterialMap;
//        $materialTarget->addMaterial(34, 10967499);
//        $materialTarget->addMaterial(35, 2743561);
//        $materialTarget->addMaterial(36, 690738);
//        $materialTarget->addMaterial(37, 171858);
//        $materialTarget->addMaterial(38, 42804);
//        $materialTarget->addMaterial(39, 9789);
//        $materialTarget->addMaterial(40, 3583);
//        $this->assertTrue($mpd->getMaterialMap() == $materialTarget);
//
//        //Improved Cloaking Device II - Tests if materials with recycle flag are handled correctly
//        $mpd = \iveeCore\SDE::instance()->getByName('Improved Cloaking Device II')->getBlueprint()->manufacture(1, -4, 0, false, 4);
//        $materialTarget = new \iveeCore\MaterialMap;
//        $materialTarget->addMaterial(9840, 10);
//        $materialTarget->addMaterial(9842, 5);
//        $materialTarget->addMaterial(11370, 1);
//        $materialTarget->addMaterial(11483, 0.15);
//        $materialTarget->addMaterial(11541, 10);
//        $materialTarget->addMaterial(11693, 10);
//        $materialTarget->addMaterial(11399, 16);
//        $this->assertTrue($mpd->getMaterialMap() == $materialTarget);
//
//        //test recursive building and adding ManufactureProcessData objects to ProcessData objects as sub-processes
//        $pd = new \iveeCore\ProcessData();
//        $pd->addSubProcessData(\iveeCore\SDE::instance()->getByName('Archon')->getBlueprint()->manufacture(1, 2, 1, true, 5));
//        $pd->addSubProcessData(\iveeCore\SDE::instance()->getByName('Rhea')->getBlueprint()->manufacture(1, -2, 1, true, 5));
//        $materialTarget = new \iveeCore\MaterialMap;
//        $materialTarget->addMaterial(34, 173107652);
//        $materialTarget->addMaterial(35, 28768725);
//        $materialTarget->addMaterial(36, 10581008);
//        $materialTarget->addMaterial(37, 1620852);
//        $materialTarget->addMaterial(38, 461986);
//        $materialTarget->addMaterial(39, 79255);
//        $materialTarget->addMaterial(40, 31920);
//        $materialTarget->addMaterial(3828, 1950);
//        $materialTarget->addMaterial(11399, 3250);
//        $materialTarget->addMaterial(16671, 9362621);
//        $materialTarget->addMaterial(16681, 33210);
//        $materialTarget->addMaterial(16682, 11520);
//        $materialTarget->addMaterial(17317, 13460);
//        $materialTarget->addMaterial(16680, 62220);
//        $materialTarget->addMaterial(16683, 11330);
//        $materialTarget->addMaterial(33362, 36600);
//        $materialTarget->addMaterial(16679, 915915);
//        $materialTarget->addMaterial(16678, 2444601);
//        $this->assertTrue($pd->getTotalMaterialMap() == $materialTarget);
//        //check skill handling
//        $skillTarget = new \iveeCore\MaterialMap;
//        $skillTarget->addSkill(22242, 4);
//        $skillTarget->addSkill(3380, 5);
//        $skillTarget->addSkill(11452, 4);
//        $skillTarget->addSkill(11454, 4);
//        $skillTarget->addSkill(11453, 4);
//        $skillTarget->addSkill(11446, 4);
//        $skillTarget->addSkill(11448, 4);
//        $skillTarget->addSkill(11443, 4);
//        $skillTarget->addSkill(11529, 4);
//        $this->assertTrue($pd->getTotalSkillMap() == $skillTarget);
    }
    
    public function testReprocessing()
    {
        $rmap = \iveeCore\Type::getByName('Arkonor')->getReprocessingMaterialMap(100, 0.5, 0.95, 1.01);
        $materialTarget = new \iveeCore\MaterialMap;
        $materialTarget->addMaterial(34, 4610);
        $materialTarget->addMaterial(36, 853);
        $materialTarget->addMaterial(39, 77);
        $materialTarget->addMaterial(40, 154);
        $this->assertTrue($rmap == $materialTarget);

        $rmap = \iveeCore\Type::getByName('Ark')->getReprocessingMaterialMap(1, 0.5, 1.0, 1.0);
        $materialTarget = new \iveeCore\MaterialMap;
        $materialTarget->addMaterial(3828, 1238);
        $materialTarget->addMaterial(11399, 2063);
        $materialTarget->addMaterial(21009, 12);
        $materialTarget->addMaterial(21017, 9);
        $materialTarget->addMaterial(21025, 17);
        $materialTarget->addMaterial(21027, 46);
        $materialTarget->addMaterial(21037, 29);
        $materialTarget->addMaterial(29039, 427);
        $materialTarget->addMaterial(29053, 348);
        $materialTarget->addMaterial(29067, 371);
        $materialTarget->addMaterial(29073, 581);
        $materialTarget->addMaterial(29095, 366);
        $materialTarget->addMaterial(29103, 581);
        $materialTarget->addMaterial(29109, 836);
        $this->assertTrue($rmap == $materialTarget);
    }
    
    public function testCopying()
    {
//        //test copying of BPs that consume materials
//        $cpd = SDE::instance()->getByName('Prototype Cloaking Device I')->getBlueprint()->copy(3, 'max', true);
//        $materialTarget = new \iveeCore\MaterialMap;
//        $materialTarget->addMaterial(3812, 6000);
//        $materialTarget->addMaterial(36, 24000);
//        $materialTarget->addMaterial(37, 45000);
//        $materialTarget->addMaterial(38, 21600);
//        $this->assertTrue($cpd->getTotalMaterialMap() == $materialTarget);
//        $this->assertTrue($cpd->getTotalTime() == 2830800);
    }

    public function testInventing()
    {
//        $ipd = SDE::instance()->getByName('Ishtar Blueprint')->invent(23185);
//        $this->assertTrue($ipd->getProbability() == 0.312);
//        $materialTarget = new \iveeCore\MaterialMap;
//        $materialTarget->addMaterial(23185, 1);
//        $materialTarget->addMaterial(20410, 8);
//        $materialTarget->addMaterial(20424, 8);
//        $materialTarget->addMaterial(25855, 0);
//        $this->assertTrue($ipd->getTotalMaterialMap() == $materialTarget);
    }

    public function testCopyInventManufacture()
    {
//        $cimpd = SDE::instance()->getByName('Ishtar Blueprint')->copyInventManufacture(23185);
//        $materialTarget = new \iveeCore\MaterialMap;
//        $materialTarget->addMaterial(38, 9320.4);
//        $materialTarget->addMaterial(3828, 420);
//        $materialTarget->addMaterial(11399, 420);
//        $materialTarget->addMaterial(16670, 767760);
//        $materialTarget->addMaterial(16680, 19530);
//        $materialTarget->addMaterial(16683, 1470);
//        $materialTarget->addMaterial(16681, 9933);
//        $materialTarget->addMaterial(16682, 2226);
//        $materialTarget->addMaterial(33359, 10080);
//        $materialTarget->addMaterial(16678, 167580);
//        $materialTarget->addMaterial(17317, 210);
//        $materialTarget->addMaterial(16679, 12600);
//        $materialTarget->addMaterial(34, 1697862);
//        $materialTarget->addMaterial(35, 373872);
//        $materialTarget->addMaterial(36, 117906);
//        $materialTarget->addMaterial(37, 29842.8);
//        $materialTarget->addMaterial(39, 1770);
//        $materialTarget->addMaterial(40, 480);
//        $materialTarget->addMaterial(23185, 3.2051282051282);
//        $materialTarget->addMaterial(20410, 25.641025641026);
//        $materialTarget->addMaterial(20424, 25.641025641026);
//        $materialTarget->addMaterial(25855, 0);
//
//        //use array_diff to compare, as otherwise the floats never match
//        $this->assertTrue(
//            array_diff(
//                $cimpd->getTotalMaterialMap()->getMaterials(),
//                $materialTarget->getMaterials()
//            ) == array()
//        );
    }
    
    public function testReaction()
    {
        $reactionProduct = \iveeCore\Type::getByName('Platinum Technite');
        $this->assertTrue($reactionProduct instanceof \iveeCore\ReactionProduct);
        //test correct handling of reaction products that can result from alchemy + refining
        $this->assertTrue($reactionProduct->getReactionIDs() == array(17952, 32831));

        //test handling of alchemy reactions with refining + feedback
        $rpd = \iveeCore\Type::getByName('Unrefined Platinum Technite Reaction')->react(24 * 30, true, true, 0.5, 1);
        $inTarget = new \iveeCore\MaterialMap;
        $inTarget->addMaterial(16640, 72000);
        $inTarget->addMaterial(16644, 7200);
        $this->assertTrue($rpd->getInputMaterialMap()->getMaterials() == $inTarget->getMaterials());
        $outTarget = new \iveeCore\MaterialMap;
        $outTarget->addMaterial(16662, 14400);
        $this->assertTrue($rpd->getOutputMaterialMap()->getMaterials() == $outTarget->getMaterials());
    }

    public function testEftFitParsing()
    {
        $fit = "

            [Naglfar, My Nag]
Republic Fleet Gyrostabilizer
Republic Fleet Gyrostabilizer

Tracking Computer II,Tracking Speed Script
Tracking Computer II,Optimal Range Script

6x2500mm Repeating Cannon I,Arch Angel Nuclear XL x1234
6x2500mm Repeating Cannon I,Arch Angel Nuclear XL
Siege Module II
";
        $pr = \iveeCore\FitParser::parseEftFit($fit);

        $materialTarget = array(
            19722 => 1,
            15806 => 2,
            1978  => 2,
            29001 => 1,
            28999 => 1,
            20452 => 2,
            20745 => 1235,
            4292  => 1
        );

        $this->assertTrue($pr->getMaterials() == $materialTarget);
    }

    public function testScanParsing()
    {
        $scanResult = "
            10MN Afterburner I
Inertia Stabilizers II
Expanded Cargohold II

1 Improved Cloaking Device II
9 Hobgoblin I
1 Siege Warfare Link - Shield Efficiency II
1 Siege Warfare Link - Active Shielding II
10  Salvage Drone I

            ";
        $pr = \iveeCore\FitParser::parseScanResult($scanResult);

        $materialTarget = array(
            12056 => 1,
            1405  => 1,
            1319  => 1,
            11577 => 1,
            2454  => 9,
            4282  => 1,
            4280  => 1,
            32787 => 10
        );

        $this->assertTrue($pr->getMaterials() == $materialTarget);
    }

    public function testXmlFitParsing()
    {
        $fitDom = new DOMDocument();
        $fitDom->loadXML('<?xml version="1.0" ?>
    <fittings>
            <fitting name="Abadong">
                <description value=""/>
                <shipType value="Abaddon"/>
                <hardware slot="low slot 0" type="Damage Control II"/>
                <hardware slot="low slot 1" type="Heat Sink II"/>
                <hardware slot="low slot 2" type="1600mm Reinforced Rolled Tungsten Plates I"/>
                <hardware slot="hi slot 7" type="Mega Pulse Laser II"/>
                <hardware slot="rig slot 2" type="Large Trimark Armor Pump I"/>
                <hardware qty="5" slot="drone bay" type="Hammerhead II"/>
                <hardware qty="5" slot="drone bay" type="Warrior II"/>
            </fitting>
        </fittings>');

        $pr = \iveeCore\FitParser::parseXmlFit($fitDom);

        $materialTarget = array(
            24692 => 1,
            2048  => 1,
            2364  => 1,
            11325 => 1,
            3057  => 1,
            25894 => 1,
            2185  => 5,
            2488  => 5
        );

        $this->assertTrue($pr->getMaterials() == $materialTarget);
    }
}
