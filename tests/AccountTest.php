<?php

namespace Finance\IA\Test;

use Decimal\Decimal;
use Finance\IA\Config\ErrorCodes;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    public function testCreateInterestAccount()
    {
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }

        $this->expectException(\Finance\IA\Exception\AccountException::class);
        $financeApiRequest = new \Finance\IA\Request\FinanceApiRequest($UUID);
        $account = new \Finance\IA\Account($UUID, $financeApiRequest);
        $account->createInterestAccount();
    }

    public function testOpenInterestAccountUnableToOpen()
    {
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }

        $financeApiRequest = \Mockery::mock('Finance\IA\Request\FinanceApiRequest');
        $financeApiRequest->shouldReceive("getInterestRate")->andReturn("0.1");
        $financeApiRequest->shouldReceive("getUserStats")->andReturn(
            new \Finance\IA\Object\UserStatsObject($UUID, "1200", false)
        );


        $account = new \Finance\IA\Account($UUID, $financeApiRequest);
        $result = $account->openInterestAccount();

        $this->assertFalse($result->success);
        $this->assertEquals(\Finance\IA\Config\ErrorCodes::ACCOUNT_NOT_SET, $result->errorCode);
        $this->assertEquals("Unable to open user account", $result->message);
    }

    public function testOpenInterestAccountOpenSuccess()
    {
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }

        $financeApiRequest = \Mockery::mock('Finance\IA\Request\FinanceApiRequest');
        $financeApiRequest->shouldReceive("getInterestRate")->andReturn(new Decimal("0.93"));
        $financeApiRequest->shouldReceive("getUserStats")->andReturn(
            new \Finance\IA\Object\UserStatsObject($UUID, "1200", true)
        );


        $account = new \Finance\IA\Account($UUID, $financeApiRequest);
        $result = $account->openInterestAccount();

        $this->assertTrue($result->success);
        $this->assertEquals(\Finance\IA\Config\ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("Interest Account is opened and active", $result->message);
    }

    public function testDepositFunds()
    {
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }

        $financeApiRequest = \Mockery::mock('Finance\IA\Request\FinanceApiRequest');
        $financeApiRequest->shouldReceive("getInterestRate")->andReturn(new Decimal("0.93"));
        $financeApiRequest->shouldReceive("getUserStats")->andReturn(
            new \Finance\IA\Object\UserStatsObject($UUID, "1200", true)
        );
        $financeApiRequest->shouldReceive("getTotalBalance")->andReturn("50000", "50050", "50100");
        $financeApiRequest->shouldReceive("depositIntoAccount")->andReturn("50050", "50100");


        $account = new \Finance\IA\Account($UUID, $financeApiRequest);
        $result = $account->openInterestAccount();

        $result = $account->depositFunds("0.8");
        $this->assertFalse($result->success);
        $this->assertEquals(\Finance\IA\Config\ErrorCodes::DEPOSIT_INVALID, $result->errorCode);
        $this->assertEquals("Deposit amount must be more than 0", $result->message);

        $result = $account->depositFunds("50");
        $this->assertTrue($result->success);
        $this->assertEquals(\Finance\IA\Config\ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("Funds have been successfully deposited", $result->message);
        $this->assertEquals("50050", $result->data['totalBalance']);

        $result = $account->depositFunds("50");
        $this->assertTrue($result->success);
        $this->assertEquals(\Finance\IA\Config\ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("Funds have been successfully deposited", $result->message);
        $this->assertEquals("50100", $result->data['totalBalance']);
    }

    public function testPayoutDumb()
    {
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }

        $financeApiRequest = \Mockery::mock('Finance\IA\Request\FinanceApiRequest');
        $financeApiRequest->shouldReceive("getUserStats")->andReturn(
            new \Finance\IA\Object\UserStatsObject($UUID, "600000", true)
        );
        $financeApiRequest->shouldReceive("depositIntoAccount")->andReturn("50");
        $financeApiRequest->shouldReceive("getTotalBalance")->andReturn("600000", "600000", "600000");
        $financeApiRequest->shouldReceive("getSkippedPayouts")->andReturn(new Decimal("0"));
        $financeApiRequest->shouldReceive("resetSkippedPayouts")->andReturn(true);
        $financeApiRequest->shouldReceive("setSkippedPayout")->andReturn(true);
        $financeApiRequest->shouldReceive("storeTransaction")->andReturn(true);
        $financeApiRequest->shouldReceive("setInterestRate")->andReturn(true);

        $date1 = new \DateTime();
        $date2 = new \DateTime();
        $date3 = new \DateTime();
        $date4 = new \DateTime();


        $financeApiRequest->shouldReceive("getInterestRate")->andReturnValues(
            [
                new Decimal("0.93"),
                new Decimal("0.93"),
                new Decimal("0.93"),
                new Decimal("0.93")
            ]
        );

        $financeApiRequest->shouldReceive("getLastPayout")->andReturnValues(
            [
                $date1,
                $date2->sub(new \DateInterval('P1D')),
                $date3->sub(new \DateInterval('P3D')),
                $date4->sub(new \DateInterval('P6D'))
            ]
        );


        $account = new \Finance\IA\Account($UUID, $financeApiRequest);
        $result = $account->openInterestAccount();

        // same day
        $result = $account->payout();
        $this->assertTrue($result->success);
        $this->assertEquals(ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("No payout due.", $result->message);

        // 1 day
        $result = $account->payout();
        $this->assertTrue($result->success);
        $this->assertEquals(ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("No payout due.", $result->message);

        // 3 days
        $result = $account->payout();
        $this->assertTrue($result->success);
        $this->assertEquals(ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("Interest has been successfully paid out.", $result->message);

        // 6 days
        $result = $account->payout();
        $this->assertFalse($result->success);
        $this->assertEquals(ErrorCodes::INTEREST_ASSIGN, $result->errorCode);
        $this->assertEquals("Interest calculation error", $result->message);
    }

    //TODO: Handle store methods
    public function testPayoutAdvanced()
    {
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        $UUID = null;
        try {
            $UUID = new \Finance\IA\Object\UUIDv4Object($uuidExample);
        } catch (\Exception $e) {
            $this->throwException($e);
        }

        $financeApiRequest = \Mockery::mock('Finance\IA\Request\FinanceApiRequest');
        $financeApiRequest->shouldReceive("getUserStats")->andReturn(
            new \Finance\IA\Object\UserStatsObject($UUID, "2000000", true)
        );

        $financeApiRequest->shouldReceive("getTotalBalance")->andReturn("2000000", "2000167", "2000335", "2000503");

        $skippedPayout1 = new Decimal("0.000");
        $skippedPayout2 = $skippedPayout1 + new Decimal("0.213");
        $skippedPayout3 = $skippedPayout2 + new Decimal("0.227");
        $skippedPayout4 = $skippedPayout3 + new Decimal("0.241");
        $financeApiRequest->shouldReceive("getSkippedPayouts")->andReturnValues(
            [
                $skippedPayout1,
                $skippedPayout2,
                $skippedPayout3,
                $skippedPayout4
            ]
        );

        $financeApiRequest->shouldReceive("resetSkippedPayouts")->andReturn(true);
        $financeApiRequest->shouldReceive("setSkippedPayout")->andReturn(true);
        $financeApiRequest->shouldReceive("storeTransaction")->andReturn(true);
        $financeApiRequest->shouldReceive("setInterestRate")->andReturn(true);
        $financeApiRequest->shouldReceive("depositIntoAccount")->andReturnValues(
            ["2000167", "2000335", "2000503", "2000668"]
        );

        $financeApiRequest->shouldReceive("getInterestRate")->andReturn(new Decimal("1.02"));

        $date1 = new \DateTime();
        $date2 = new \DateTime();
        $date3 = new \DateTime();
        $date4 = new \DateTime();

        echo "test \n";
        $financeApiRequest->shouldReceive("getLastPayout")->andReturnValues(
            [
                $date1,
                $date2->sub(new \DateInterval('P3D')),
                $date3->sub(new \DateInterval('P3D')),
                $date4->sub(new \DateInterval('P3D'))
            ]
        );


        $account = new \Finance\IA\Account($UUID, $financeApiRequest);
        $result = $account->openInterestAccount();

        // same day
        $result = $account->payout();
        $this->assertTrue($result->success);
        $this->assertEquals(ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("No payout due.", $result->message);

        // 3 days
        $payout1 = 2000000 + (2000000 * (1.02 / 100)) / (365 / 3);
        $result = $account->payout();
        $this->assertTrue($result->success);
        $this->assertEquals(ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("Interest has been successfully paid out.", $result->message);
        $this->assertEquals(floor($payout1), $result->data['totalBalance']);
        $payoutCheck = round($payout1 - floor($payout1), 3);
        $this->assertEquals(new Decimal($payoutCheck . "", 3), new Decimal($result->data['skippedPayout'], 3));


        // 6 days
        $payout2 = $payout1 + ($payout1 * (1.02 / 100)) / (365 / 3);
        $result = $account->payout();
        $this->assertTrue($result->success);
        $this->assertEquals(ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("Interest has been successfully paid out.", $result->message);
        $this->assertEquals(round($payout2, 0), round($result->data['totalBalance'], 0));
        $payoutCheck = round($payout2 - floor($payout2), 3);
        $this->assertEquals(new Decimal($payoutCheck . "", 3), new Decimal($result->data['skippedPayout'], 3));


        // 9 days
        $payout3 = $payout2 + ($payout2 * (1.02 / 100)) / (365 / 3);
        $result = $account->payout();
        $this->assertTrue($result->success);
        $this->assertEquals(ErrorCodes::NONE, $result->errorCode);
        $this->assertEquals("Interest has been successfully paid out.", $result->message);
        $this->assertEquals(round($payout3, 0), round($result->data['totalBalance'], 0));
        $payoutCheck = round($payout3 - floor($payout3), 3);
        $this->assertEquals(new Decimal($payoutCheck . "", 3), new Decimal($result->data['skippedPayout'], 3));
    }
}