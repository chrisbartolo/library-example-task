<?php

namespace Finance\IA\Processor;

use Decimal\Decimal;
use Finance\IA\Config\InterestIntervalConfig;
use Finance\IA\Config\InterestRatesConfig;
use Finance\IA\Exception\InterestException;
use Finance\IA\Object\UserStatsObject;


/**
 * Functionality required for Interest Rates checks and handling
 * @package Chip\IA\Processor
 */
class InterestProcessor
{
    private array $interest_rates;

    public function __construct()
    {
        $InterestRatesConfig = new InterestRatesConfig();
        $this->interest_rates = $InterestRatesConfig->getRates();
    }

    /**
     * Select rate based on provided requirements which are in config
     *
     * @param int $monthlyIncome value in pennies
     * @return Decimal
     * @throws InterestException
     * @todo Rates should not be part of the library, but retrieved by API to prevent modification
     */
    public function selectRate(int $monthlyIncome = null): Decimal
    {
        if ($monthlyIncome == null) {
            $monthlyIncome = 0;
        }
        if ($monthlyIncome < 1) {
            foreach ($this->interest_rates as $rate) {
                if ($rate->minMonthlyIncome == 0) {
                    return $rate->yearlyInterestRate;
                }
            }
        }
        foreach ($this->interest_rates as $rate) {
            if ($monthlyIncome >= $rate->minMonthlyIncome && $monthlyIncome <= $rate->maxMonthlyIncome ||
                $monthlyIncome >= $rate->minMonthlyIncome && $rate->maxMonthlyIncome == 0) {
                return $rate->yearlyInterestRate;
            }
        }

        throw new InterestException("No interest rate is available for given monthly income");
    }

    /**
     * Calculate interest due
     *
     * @param UserStatsObject $userStats
     * @return Decimal
     * @throws InterestException
     */
    public function calculateInterest(UserStatsObject $userStats): Decimal
    {
        $numberOfPayouts = $this->calculatePayoutCount($userStats);


        if ($numberOfPayouts > 1) {
            throw new InterestException("Handling of missed payout days not yet implemented");
        }

        if ($numberOfPayouts >= 1) {
            return $this->compoundInterest($userStats->getTotalBalance(), (int) floor(365/3), $userStats->getInterestRate());
        }
        return new Decimal("0.0");
    }

    private function compoundInterest($principal, $periods, $interest): Decimal
    {
        $converted_interest = $interest / 100;
        $completed_interest = $converted_interest + 1;
        $exponent = pow($completed_interest, $periods);
        return new Decimal("".$principal * $exponent);
    }

    /**
     * Calculate how many days have passed since last payout
     *
     * @param UserStatsObject $userStats
     * @return int days from payout
     */
    public function calculatePayoutCount(UserStatsObject $userStats): int
    {
        $daysToPayout = $userStats->getDaysFromLastPayout();
        return floor($daysToPayout / InterestIntervalConfig::PAYOUT_INTERVAL_DAYS);
    }
}

?>