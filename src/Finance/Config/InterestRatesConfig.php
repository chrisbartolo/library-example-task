<?php

namespace Finance\IA\Config;

use Decimal\Decimal;
use Finance\IA\Object\InterestRateObject;


class InterestRatesConfig
{
    private $rates;

    public function __construct()
    {
        $this->setRates();
    }

    private function setRates()
    {
        $this->rates = array();
        $this->rates[] = new InterestRateObject(new Decimal("0.5"), 0, 0);
        $this->rates[] = new InterestRateObject(new Decimal("0.93"), 1, 4999);
        $this->rates[] = new InterestRateObject(new Decimal("1.02"), 5000, 0);
    }

    /**
     * @return mixed
     */
    public function getRates()
    {
        return $this->rates;
    }

}

?>