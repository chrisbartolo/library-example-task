<?php

namespace Finance\IA\Object;

use DateTime;
use Decimal\Decimal;
use Finance\IA\Request\FinanceApiRequest;

/**
 * User Stats as an object for easy re-use between classes and methods
 * @package Finance\IA\Object
 */
class UserStatsObject
{
    private bool $active;
    private UUIDv4Object $uuid;
    private int $monthly_income;
    private Decimal $interest_rate;
    private array $statement;
    private int $total_balance;
    private int $interest_payout_completed;
    private int $interest_payout_pending;
    private DateTime $last_payout_date;


    public function __construct(UUIDv4Object $uuid, int $monthly_income = 0, bool $active = false)
    {
        $this->uuid = $uuid;
        $this->monthly_income = $monthly_income;
        $this->active = $active;
    }

    /**
     * @return int
     */
    public function getMonthlyIncome(): int
    {
        return $this->monthly_income;
    }

    /**
     * @return UUIDv4Object
     */
    public function getUuid(): UUIDv4Object
    {
        return $this->uuid;
    }

    /**
     * @return array
     */
    public function getStatement(): array
    {
        return $this->statement;
    }

    /**
     * @param array $statement
     */
    public function setStatement(array $statement): void
    {
        $this->statement = $statement;
    }

    /**
     * @return int
     */
    public function getInterestPayoutPending(): int
    {
        return $this->interest_payout_pending;
    }

    /**
     * @param int $interest_payout_pending
     */
    public function setInterestPayoutPending(int $interest_payout_pending): void
    {
        $this->interest_payout_pending = $interest_payout_pending;
    }

    /**
     * @return int
     */
    public function getInterestPayoutCompleted(): int
    {
        return $this->interest_payout_completed;
    }

    /**
     * @param int $interest_payout_completed
     */
    public function setInterestPayoutCompleted(int $interest_payout_completed): void
    {
        $this->interest_payout_completed = $interest_payout_completed;
    }

    /**
     * @return Decimal
     */
    public function getInterestRate(): Decimal
    {
        return $this->interest_rate;
    }

    /**
     * @param Decimal $interest_rate
     */
    public function setInterestRate(Decimal $interest_rate): void
    {
        $this->interest_rate = $interest_rate;
    }

    /**
     * @return int
     */
    public function getTotalBalance(): int
    {
        return $this->total_balance;
    }

    /**
     * @param int $total_balance
     */
    public function setTotalBalance(int $total_balance): void
    {
        $this->total_balance = $total_balance;
    }

    /**
     * @param DateTime $last_payout_date
     */
    public function setLastPayoutDate(DateTime $last_payout_date): void
    {
        $this->last_payout_date = $last_payout_date;
    }

    /**
     * @return int
     */
    public function getDaysFromLastPayout(): int
    {
        $now = new DateTime();

        return $now->diff($this->last_payout_date)->format("%a");
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    public function fetchData(FinanceApiRequest $financeApiRequest)
    {
        $this->setLastPayoutDate($financeApiRequest->getLastPayout());
        $this->setInterestRate($financeApiRequest->getInterestRate());
        $this->setTotalBalance($financeApiRequest->getTotalBalance());
    }
}

?>