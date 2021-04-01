<?php

namespace Finance\IA\Config;

use Finance\IA\Object\InterestRateObject;
use Decimal\Decimal;


class InterestRatesConfig
{
    private $_rates;

    public function __construct()
    {
        $this->setRates();
    }

    private function setRates()
    {
        $this->_rates = array();
        $this->_rates[] = new InterestRateObject(new Decimal("0.5"), 0, 0);
        $this->_rates[] = new InterestRateObject(new Decimal("0.93"), 1, 4999);
        $this->_rates[] = new InterestRateObject(new Decimal("1.02"), 5000, 0);
    }

    /**
     * @return mixed
     */
    public function getRates()
    {
        return $this->_rates;
    }

}

?>