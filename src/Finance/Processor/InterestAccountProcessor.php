<?php

namespace Finance\IA\Processor;

use Finance\IA\Config\ErrorCodes;
use Finance\IA\Exception\InterestException;
use Finance\IA\Object\ResultObject;
use Finance\IA\Object\UserStatsObject;
use Finance\IA\Object\UUIDv4Object;
use Finance\IA\Request\FinanceApiRequest;

/**
 * A basic middle-service interface to be used. Can be ommitted, but provides an easier way to mock for creation of unit tests
 * @package Finance\IA\Processor
 */
class InterestAccountProcessor
{
    private FinanceApiRequest $financeApiRequest;

    public function __construct(UUIDv4Object $uuid, $financeApiRequest = null)
    {
        if ($financeApiRequest) {
            $this->financeApiRequest = $financeApiRequest;
        } else {
            $this->setChatApiRequest($uuid);
        }
    }

    public function setChatApiRequest(UUIDv4Object $uuid)
    {
        $this->financeApiRequest = new financeApiRequest($uuid);
    }

    public function activateAccount(): UserStatsObject
    {
        $resultObject = new ResultObject();

        $userStats = $this->financeApiRequest->getUserStats();

        return $userStats;

        if ($userStats->isActive()) {
            $resultObject->setErrorCode(ErrorCodes::NONE);
            $resultObject->setSuccess(1);
            $resultObject->setMessage("Account has been activated");
            $resultObject->setData(["userStats" => $userStats]);
            return $resultObject;
        } else {
            $resultObject->setErrorCode(ErrorCodes::ACCOUNT_NOT_SET);
            $resultObject->setSuccess(0);
            $resultObject->setMessage("Unable to open user account");
            return $resultObject;
        }
    }

    public function getInterestRate(): ResultObject
    {
        $resultObject = new ResultObject();
        $interestRate = $this->financeApiRequest->getInterestRate();

        // if its the first time we're assigning a rate, store it for future use
        if ($interestRate == 0.0) {
            $interestProcessor = new InterestProcessor();
            try {
                $interestRate = $interestProcessor->selectRate($this->getUserStats()->getMonthlyIncome());
                $this->getFinanceApiRequest()->setInterestRate($interestRate);

                $resultObject->setErrorCode(ErrorCodes::NONE);
                $resultObject->setMessage("Rate successfully assigned");
                $resultObject->setSuccess(1);
                $resultObject->setData(["rate" => $interestRate]);
            } catch (InterestException $message) {
                $resultObject->setErrorCode(ErrorCodes::INTEREST_ASSIGN);
                $resultObject->setMessage($message);
                $resultObject->setSuccess(0);
            }
        } else {
            $resultObject->setErrorCode(ErrorCodes::NONE);
            $resultObject->setMessage("Rate successfully retrieved");
            $resultObject->setSuccess(1);
            $resultObject->setData(["rate" => $interestRate]);
        }
        return $resultObject;
    }

    public function getFinanceApiRequest(): FinanceApiRequest
    {
        return $this->financeApiRequest;
    }

}

?>