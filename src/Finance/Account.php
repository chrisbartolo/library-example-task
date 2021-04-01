<?php

namespace Finance\IA;

use Exception;
use Finance\IA\Config\ErrorCodes;
use Finance\IA\Exception\AccountException;
use Finance\IA\Exception\InterestException;
use Finance\IA\Exception\UuidException;
use Finance\IA\Object\ResultObject;
use Finance\IA\Object\UserStatsObject;
use Finance\IA\Object\UUIDv4Object;
use Finance\IA\Processor\InterestAccountProcessor;
use Finance\IA\Processor\InterestProcessor;
use Finance\IA\Request\FinanceApiRequest;
use GuzzleHttp\Exception\GuzzleException;

/**
 * This is the main class that provides all the functionality needed to manipulate a Finance Interest Account.
 * @package Finance\IA
 */
class Account
{

    private UserStatsObject $userStats;
    private UUIDv4Object $uuid;
    private InterestAccountProcessor $interestAccountProcessor;

    public function __construct(UUIDv4Object $uuid, FinanceApiRequest $financeApiRequest)
    {
        $this->setUuid($uuid);
        $this->interestAccountProcessor = new InterestAccountProcessor($uuid, $financeApiRequest);
    }

    /**
     * @return UUIDv4Object
     */
    public function getUuid(): UUIDv4Object
    {
        return $this->uuid;
    }

    private function setUuid(UUIDv4Object $uuid)
    {
        if(isset($this->uuid)) {
            throw new UuidException("Only one active user allowed.");
        }
        $this->uuid = $uuid;
    }

    /**
     * Not currently supported or offered.
     *
     * @throws AccountException
     * @todo Add support for external creation of Interest Accounts by third parties
     */
    public function createInterestAccount()
    {
        $this->setUserStats($this->getFinanceApiRequest()->getUserStats());

        if ($this->getUserStats() != null) {
            throw new AccountException("Can't open account, user already has an active account.");
        }

        //Intentionally not caught
        throw new Exception("Functionality not available");
    }

    private function setUserStats(UserStatsObject $userStatsObject)
    {
        $this->userStats = $userStatsObject;
    }

    public function getFinanceApiRequest(): FinanceApiRequest
    {
        return $this->interestAccountProcessor->getFinanceApiRequest();
    }

    private function getUserStats(): UserStatsObject
    {
        return $this->userStats;
    }

    /**
     * Open and activated a Finance Interest Account.
     * Once called, the interest rate assigned to the account is fetched from the API. If its not provided, we calculate the rate and assign it.
     * Only 1 open and active account is supported by this library.
     *
     * @return ResultObject Standard object to easily process results
     */
    public function openInterestAccount(): ResultObject
    {
        // initiate dependencies
        $resultObject = new ResultObject();
        $userStats = $this->interestAccountProcessor->activateAccount();
        if(!$userStats->isActive())
        {
            return $resultObject->returnResult($resultObject::RESULT_ACCOUNT_NOT_ACTIVATED);
        }
        $this->setUserStats($userStats);

        // an interest account needs a new rate, only if its the first time being opened; otherwise use the one already assigned
        $resultObject = $this->interestAccountProcessor->getInterestRate();
        if (!$resultObject->success) {
            return $resultObject;
        }
        $this->getUserStats()->setInterestRate($resultObject->data['rate']);

        return $resultObject->returnResult(ResultObject::RESULT_OPENED_AND_ACTIVE);
    }

    /**
     *
     * @return ResultObject
     */
    public function listStatement(): ResultObject
    {
        // initiate dependencies
        $resultObject = new ResultObject();
        $userStats = $this->interestAccountProcessor->activateAccount();
        if(!$userStats->isActive())
        {
            return $resultObject->returnResult($resultObject::RESULT_ACCOUNT_NOT_ACTIVATED);
        }
        $this->setUserStats($userStats);
        $resultObject->clearResult();

        $this->getFinanceApiRequest()->getUserStatement();
        return $resultObject;
    }

    /**
     * Deposit funds into the account
     *
     * @param int $deposit_amount amount to deposit in pennies
     * @return ResultObject Standard object to easily process results
     * @throws Exception
     */
    public function depositFunds(int $deposit_amount): ResultObject
    {
        // initiate dependencies
        $resultObject = new ResultObject();
        $userStats = $this->interestAccountProcessor->activateAccount();
        if(!$userStats->isActive())
        {
            return $resultObject->returnResult($resultObject::RESULT_ACCOUNT_NOT_ACTIVATED);
        }
        $this->setUserStats($userStats);
        $resultObject->clearResult();

        if ($deposit_amount < 1) {
            return $resultObject->returnResult(ResultObject::RESULT_DEPOSIT_AMOUNT_MIN_LIMIT);
        }

        $totalBalance = $this->getFinanceApiRequest()->depositIntoAccount($deposit_amount);
        if ($totalBalance == false) {
            throw new Exception("Deposit funds fatal error. Contact support");
        }

        $this->getUserStats()->setTotalBalance($totalBalance);

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
     * @throws GuzzleException
     */
    public function payout(): ResultObject
    {
        // initiate dependencies
        $resultObject = new ResultObject();
        $userStats = $this->interestAccountProcessor->activateAccount();
        if(!$userStats->isActive())
        {
            return $resultObject->returnResult($resultObject::RESULT_ACCOUNT_NOT_ACTIVATED);
        }
        $this->setUserStats($userStats);
        $this->userStats->fetchData($this->getFinanceApiRequest());
        $resultObject->clearResult();

        $interestProcessor = new InterestProcessor();

        if ($interestProcessor->calculatePayoutCount($this->getUserStats()) == 0) {
            return $resultObject->returnResult($resultObject::RESULT_PAYOUT_COUNT_FAILED);
        }

        try {
            $interestValue = $interestProcessor->calculateInterest($this->getUserStats());
        } catch (InterestException $e) {
            return $resultObject->returnResult($resultObject::RESULT_INTEREST_FAILED);
        }

        if ($interestValue == 0.00) {
            return $resultObject->returnResult($resultObject::RESULT_INTEREST_ZERO);
        }

        $totalInterestToPayout = $interestValue + $this->getFinanceApiRequest()->getSkippedPayouts();
        if ($totalInterestToPayout < 1) {
            $this->getFinanceApiRequest()->setSkippedPayout($interestValue);
            $this->getFinanceApiRequest()->storeTransaction();

            return $resultObject->returnResult(ResultObject::RESULT_DEPOSIT_PAYOUT_LESS_MINIMUM);
        }

        $totalPayoutToSkip = $totalInterestToPayout - intval($totalInterestToPayout);

        $this->getUserStats()->setTotalBalance(
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
                "totalBalance" => $this->getUserStats()->getTotalBalance(),
                "totalPaid" => $totalInterestToPayout,
                "skippedPayout" => $totalPayoutToSkip
            ]
        );
        return $resultObject;
    }

}