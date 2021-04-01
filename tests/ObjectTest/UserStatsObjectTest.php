<?php
namespace Finance\IA\Test\ObjectTest;

use PHPUnit\Framework\TestCase;

final class UserStatsObjectTest extends TestCase
{
    public function testGetDaysFromLastPayout()
    {
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }


        $userStatsObject = new \Finance\IA\Object\UserStatsObject($UUID);


        $date = new \DateTime();
        $date->sub(new \DateInterval('P3D'));

        $userStatsObject->setLastPayoutDate($date);
        $this->assertEquals("3", $userStatsObject->getDaysFromLastPayout());


        $date->sub(new \DateInterval('P4D'));
        $userStatsObject->setLastPayoutDate($date);
        $this->assertEquals("7", $userStatsObject->getDaysFromLastPayout());

        $date->sub(new \DateInterval('P2D'));
        $userStatsObject->setLastPayoutDate($date);
        $this->assertEquals("9", $userStatsObject->getDaysFromLastPayout());
    }

    public function testGetDaysFromLastPayoutFail()
    {
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }


        $userStatsObject = new \Finance\IA\Object\UserStatsObject($UUID);


        $date = new \DateTime();
        $date->sub(new \DateInterval('P3D'));

        $userStatsObject->setLastPayoutDate($date);
        $this->assertNotEquals("5", $userStatsObject->getDaysFromLastPayout());
    }
}

?>