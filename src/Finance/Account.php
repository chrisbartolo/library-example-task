<?php

namespace Finance\IA;

use Finance\IA\Config\ErrorCodes;
use Finance\IA\Exception\AccountException;
use Finance\IA\Exception\InterestException;
use Finance\IA\Object\ResultObject;
use Finance\IA\Object\UserStatsObject;
use Finance\IA\Object\UUIDv4Object;
use Finance\IA\Processor\InterestAccountProcessor;
use Finance\IA\Processor\InterestProcessor;
use Finance\IA\Request\FinanceApiRequest;
use Exception;

/**
 * This is the main class that provides all the functionality needed to manipulate a Finance Interest Account.
 * @package Finance\IA
 */
class Account
{

    private static UserStatsObject $userStats;
    private static UUIDv4Object $uuid;
    private InterestAccountProcessor $interestAccountProcessor;

    public function __construct(UUIDv4Object $uuid, FinanceApiRequest $financeApiRequest)
    {
        self::setUuid($uuid);
        $this->interestAccountProcessor = new InterestAccountProcessor($uuid, $financeApiRequest);
    }

    public function getFinanceApiRequest(): FinanceApiRequest
    {
        return $this->interestAccountProcessor->getFinanceApiRequest();
    }

    /**
     * Not currently supported or offered.
     *
     * @param int|null $income
     * @throws AccountException
     * @todo Add support for external creation of Interest Accounts by third parties
     */
    public function createInterestAccount(int $income = null)
    {
        self::$userStats = $this->getFinanceApiRequest()->getUserStats();

        if (self::$userStats != null) {
            throw new AccountException("Can't open account, user already has an active account.");
        }

        //Intentionally not caught
        throw new Exception("Functionality not available");
    }

    /**
     * Open and activated a Finance Interest Account.
     * Once called, the interest rate assigned to the account is fetched from the API. If its not provided, we calculate the rate and assign it.
     * Only 1 open and active account is supported by this library.
     *
     * @param int|null $income Amount of monthly income in pennies
     * @return ResultObject Standard object to easily process results
     */
    public function openInterestAccount(int $income = null): ResultObject
    {
        // initiate dependencies
        $resultObject = new ResultObject();

        self::$userStats = $this->interestAccountProcessor->activateAccount(self::getUuid());

        // we can only open an account if it exists
        if (self::$userStats == null || !self::$userStats->isActive()) {
            //throw new AccountException("Can't set account, user does not exist.");
            $resultObject->setErrorCode(ErrorCodes::ACCOUNT_NOT_SET);
            $resultObject->setSuccess(0);
            $resultObject->setMessage("Unable to open user account");
            return $resultObject;
        }

        // an interest account needs a new rate, only if its the first time being opened; otherwise use the one already assigned
        $interestRate = $this->interestAccountProcessor->getInterestRate();

        // if its the first time we're assigning a rate, store it for future use
        if ($interestRate == 0.0) {
            $interestProcessor = new InterestProcessor();
            try {
                $interestRate = $interestProcessor->selectRate(self::$userStats->getMonthlyIncome());
                $this->getFinanceApiRequest()->setInterestRate($interestRate);
            } catch (InterestException $message) {
                $resultObject->setErrorCode(ErrorCodes::INTEREST_ASSIGN);
                $resultObject->setMessage($message);
                $resultObject->setSuccess(0);
                return $resultObject;
            }
        }

        $this::$userStats->setInterestRate($interestRate);

        //Set the result object with success
        $resultObject->setMessage("Interest Account is opened and active");
        $resultObject->setErrorCode(ErrorCodes::NONE);
        $resultObject->setSuccess(1);
        return $resultObject;
    }

    /**
     * @return UUIDv4Object
     */
    public static function getUuid(): UUIDv4Object
    {
        return self::$uuid;
    }

    public static function setUuid(UUIDv4Object $uuid)
    {
        self::$uuid = $uuid;
    }

    /**
     *
     * @return ResultObject
     */
    public function listStatement(): ResultObject
    {
        // initiate dependencies
        $resultObject = new ResultObject();

        if ($this::$userStats != null || !$this::$userStats->isActive()) {
            $resultObject->setErrorCode(ErrorCodes::ACCOUNT_NOT_SET);
            $resultObject->setSuccess(0);
            $resultObject->setMessage("No user account is currently open");
            return $resultObject;
        }

        $this->getFinanceApiRequest()->getUserStatement();
        return $resultObject;
    }

    /**
     * Deposit funds into the account
     *
     * @param int $deposit_amount amount to deposit in pennies
     * @return ResultObject Standard object to easily process results
     */
    public function depositFunds(int $deposit_amount): ResultObject
    {
        // initiate dependencies
        $resultObject = new ResultObject();

        if (self::$userStats == null || !self::$userStats->isActive()) {
            $resultObject->setErrorCode(ErrorCodes::ACCOUNT_NOT_SET);
            $resultObject->setSuccess(0);
            $resultObject->setMessage("No user account is currently open");
            return $resultObject;
        }

        if ($deposit_amount < 1) {
            $resultObject->setErrorCode(ErrorCodes::DEPOSIT_INVALID);
            $resultObject->setSuccess(0);
            $resultObject->setMessage("Deposit amount must be more than 0");
            return $resultObject;
        }


        $totalBalance = $this->getFinanceApiRequest()->depositIntoAccount($deposit_amount);
        if($totalBalance == false) {
            throw new Exception("Deposit funds fatal error. Contact support");
        }

        self::$userStats->setTotalBalance($totalBalance);

        $resultObject->setSuccess(1);
        $resultObject->setErrorCode(ErrorCodes::NONE);
        $resultObject->setMessage("Funds have been successfully deposited");
        $resultObject->setData(
            array(
                "totalBalance" => $totalBalance
            )
        );
        return $resultObject;
    }

    /**
     * Calculate how much needs to be paid out in interest rate, including previously skipped values.
     *
     * @return ResultObject
     */
    public function payout(): ResultObject
    {
        // initiate dependencies
        $resultObject = new ResultObject();

        if (self::$userStats == null || !self::$userStats->isActive()) {
            $resultObject->setErrorCode(ErrorCodes::ACCOUNT_NOT_SET);
            $resultObject->setSuccess(0);
            $resultObject->setMessage("No user account is currently open");
            return $resultObject;
        }

        $interestProcessor = new InterestProcessor();

        self::$userStats->setLastPayoutDate($this->getFinanceApiRequest()->getLastPayout());
        self::$userStats->setInterestRate($this->getFinanceApiRequest()->getInterestRate());
        self::$userStats->setTotalBalance($this->getFinanceApiRequest()->getTotalBalance());


        if ($interestProcessor->calculatePayoutCount(self::$userStats) == 0) {
            $resultObject->setErrorCode(ErrorCodes::NONE);
            $resultObject->setSuccess(1);
            $resultObject->setMessage("No payout due.");
            return $resultObject;
        }

        try {
            $interestValue = $interestProcessor->calculateInterest(self::$userStats);
        } catch (InterestException $e) {
            $resultObject->setErrorCode(ErrorCodes::INTEREST_ASSIGN);
            $resultObject->setSuccess(0);
            $resultObject->setMessage("Interest calculation error");
            return $resultObject;
        }
        if ($interestValue == 0.00) {
            $resultObject->setErrorCode(ErrorCodes::NONE);
            $resultObject->setSuccess(1);
            $resultObject->setMessage("No interest has been accumulated.");
            return $resultObject;
        }

        $totalInterestToPayout = $interestValue + $this->getFinanceApiRequest()->getSkippedPayouts();
        if ($totalInterestToPayout < 1) {
            $this->getFinanceApiRequest()->setSkippedPayout($interestValue);
            $this->getFinanceApiRequest()->storeTransaction();

            $resultObject->setErrorCode(ErrorCodes::NONE);
            $resultObject->setSuccess(1);
            $resultObject->setMessage(
                "Interest accumulated is less than 1 penny. It will be included in the next payout which totals to at least 1 penny."
            );
            return $resultObject;
        }

        $totalPayoutToSkip = $totalInterestToPayout - intval($totalInterestToPayout);

        self::$userStats->setTotalBalance(
            $this->getFinanceApiRequest()->depositIntoAccount(intval($totalInterestToPayout))
        );
        $this->getFinanceApiRequest()->resetSkippedPayouts();

        //We have to set the amount of skipped payouts again, as we don't support decimals in balances. We store the pending <1 penny amount. This way the user does not lose.
        $this->getFinanceApiRequest()->setSkippedPayout($totalPayoutToSkip);

        $this->getFinanceApiRequest()->storeTransaction();

        $resultObject->setErrorCode(ErrorCodes::NONE);
        $resultObject->setSuccess(1);
        $resultObject->setMessage("Interest has been successfully paid out.");
        $resultObject->setData(
            [
                "totalBalance" => self::$userStats->getTotalBalance(),
                "totalPaid" => $totalInterestToPayout,
                "skippedPayout" => $totalPayoutToSkip
            ]
        );
        return $resultObject;
    }

}