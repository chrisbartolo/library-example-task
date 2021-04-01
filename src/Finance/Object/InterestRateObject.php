<?php

namespace Finance\IA\Object;

use Decimal\Decimal;

class InterestRateObject
{
    public Decimal $yearlyInterestRate;
    public int $minMonthlyIncome;
    public int $maxMonthlyIncome;

    public function __construct($yearlyInterestRate, $minMonthlyIncome, $maxMonthlyIncome)
    {
        $this->yearlyInterestRate = $yearlyInterestRate;
        $this->minMonthlyIncome = $minMonthlyIncome;
        $this->maxMonthlyIncome = $maxMonthlyIncome;
    }
}

?>