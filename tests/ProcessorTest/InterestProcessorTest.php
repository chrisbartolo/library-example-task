<?php
namespace Finance\IA\Test\ProcessorTest;

use Finance\IA\Object\UserStatsObject;
use Decimal\Decimal;
use PHPUnit\Framework\TestCase;

final class InterestProcessorTest extends TestCase
{
    public function testSelectRate()
    {
        $interestProcessor = new \Finance\IA\Processor\InterestProcessor();

        //Tests for unknown value
        $this->assertEquals(new Decimal("0.5"), $interestProcessor->selectRate(0));
        $this->assertEquals(new Decimal("0.5"), $interestProcessor->selectRate(null));

        //Tests for known value, first tier
        $this->assertEquals(new Decimal("0.93"), $interestProcessor->selectRate(1));
        $this->assertEquals(new Decimal("0.93"), $interestProcessor->selectRate(1000));
        $this->assertEquals(new Decimal("0.93"), $interestProcessor->selectRate(4999));


        //Tests for known value, second tier
        $this->assertEquals(new Decimal("1.02"), $interestProcessor->selectRate(5000));
        $this->assertEquals(new Decimal("1.02"), $interestProcessor->selectRate(6000));
        $this->assertEquals(new Decimal("1.02"), $interestProcessor->selectRate(9999999999));
    }

    public function testCalculatePayoutCount()
    {
        $interestProcessor = new \Finance\IA\Processor\InterestProcessor();
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }

        $userStatsObject = new UserStatsObject($UUID);
        $userStatsObject->setLastPayoutDate(new \DateTime(""));

        $date = new \DateTime();
        $date->sub(new \DateInterval('P10D'));
        $userStatsObject->setLastPayoutDate($date);

        $this->assertEquals(3, $interestProcessor->calculatePayoutCount($userStatsObject));

        $date->sub(new \DateInterval('P1D'));
        $userStatsObject->setLastPayoutDate($date);
        $this->assertEquals(3, $interestProcessor->calculatePayoutCount($userStatsObject));
    }

    public function testCalculateInterest()
    {
        $interestProcessor = new \Finance\IA\Processor\InterestProcessor();
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }

        $userStatsObject = new UserStatsObject($UUID, "6000");

        $date = new \DateTime();
        $date->sub(new \DateInterval('P3D'));
        $userStatsObject->setLastPayoutDate($date);
        $userStatsObject->setInterestRate(new Decimal("0.93"));
        $userStatsObject->setTotalBalance("6000");

        $totalPayoutsAnnually = new Decimal(floor(365 / 3)."");

        $totalResult = $userStatsObject->getInterestRate() / 100;
        $totalResult = $totalResult * $userStatsObject->getTotalBalance();
        $totalResult = $totalResult / $totalPayoutsAnnually;

        $this->assertEquals(new Decimal($totalResult.""), $interestProcessor->calculateInterest($userStatsObject));
    }
}