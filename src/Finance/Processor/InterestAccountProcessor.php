<?php

namespace Finance\IA\Processor;

use Finance\IA\Object\UserStatsObject;
use Finance\IA\Object\UUIDv4Object;
use Finance\IA\Request\FinanceApiRequest;
use Decimal\Decimal;

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

    public function getFinanceApiRequest(): FinanceApiRequest
    {
        return $this->financeApiRequest;
    }

    public function activateAccount(UUIDv4Object $uuid): UserStatsObject
    {
        return $this->financeApiRequest->getUserStats();
    }

    public function getInterestRate(): Decimal
    {
        return $this->financeApiRequest->getInterestRate();
    }

}

?>