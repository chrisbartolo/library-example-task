<?php

namespace Finance\IA\Request;

use Finance\IA\Exception\InterestException;
use Finance\IA\Exception\UuidException;
use Finance\IA\Object\UserStatsObject;
use Finance\IA\Object\UUIDv4Object;
use DateTime;
use Decimal\Decimal;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * API helper for the Finance Internet Account remote requests
 * @package Finance\IA\Request
 */
class FinanceApiRequest
{

    public const API_URL = "https://stats.dev.finance.test";

    private UserStatsObject $userStats;
    private UUIDv4Object $UUIDv4Object;
    private Client $guzzleClient;

    public function __construct(UUIDv4Object $uuid = null)
    {
        $this->guzzleClient = new Client();

        if ($uuid != null) {
            $this->setUUID($uuid);
            $this->setUserStats($uuid);
        }
    }

    public function setUUID(UUIDv4Object $uuid)
    {
        $this->UUIDv4Object = $uuid;
    }

    /**
     * @return UserStatsObject
     */
    public function getUserStats(): UserStatsObject
    {
        return $this->userStats;
    }

    public function setUserStats(UUIDv4Object $uuid)
    {
        $this->userStats = $this->getUserData();
    }

    /**
     * Set the interest rate through the API. The interest rate can only be set if there is no pre-assigned rate on the account.
     *
     * @param Decimal $interestRate
     * @return bool
     * @throws GuzzleException
     * @throws InterestException
     */
    public function setInterestRate(Decimal $interestRate): bool
    {
        $uuid = $this->UUIDv4Object->getUUID();
        $interestRateResult = $this->getInterestRate();
        if ($interestRateResult > 0) {
            throw new InterestException("Interest rate already is set for active user interest account");
        }

        $response = $this->guzzleClient->request(
            'POST',
            self::API_URL . "/users/" . $uuid . "/rate",
            [
                'rate' => $interestRate
            ]
        );

        if ($response->getStatusCode() == 200) {
            return true;
        }

        return false;
    }

    /**
     * Get the interest rate if it has already been assigned to the account.
     *
     * @return Decimal 0 if no interest rate has been assigned
     */
    public function getInterestRate(): Decimal
    {
        $uuid = $this->UUIDv4Object->getUUID();
        $UUID = null;
        try {
            $response = $this->guzzleClient->request('GET', self::API_URL . "/users/" . $uuid . "/rate");

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());

                return new Decimal($data->yearly_interest_rate . "");
            }
        } catch (GuzzleException $e) {
        }

        return new Decimal("0.0");
    }

    /**
     * Add funds to the account
     *
     * @param int $amountInPennies
     * @return int total balance after adding funds
     * @throws GuzzleException
     */
    public function depositIntoAccount(int $amountInPennies): int
    {
        $uuid = $this->UUIDv4Object->getUUID();

        $response = $this->guzzleClient->request(
            'POST',
            self::API_URL . "/users/" . $uuid . "/deposit",
            [
                'amount_in_pennies' => $amountInPennies
            ]
        );

        if ($response->getStatusCode() == 200) {
            return $this->getTotalBalance();
        }

        return false;
    }

    /**
     * Get the total balance inside the user's account
     * @return int amount in pennies
     */
    public function getTotalBalance(): int
    {
        $uuid = $this->UUIDv4Object->getUUID();

        try {
            $response = $this->guzzleClient->request('GET', self::API_URL . "/users/" . $uuid . "/balance");

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());

                return (int)$data->balance;
            }
        } catch (GuzzleException $e) {
        }
        return 0;
    }

    /**
     * Send to the API the skipped payout so that it can be used and accumulated towards future payouts
     *
     * @param Decimal $amount_skipped
     * @return bool|int
     * @throws GuzzleException
     */
    public function setSkippedPayout(Decimal $amount_skipped)
    {
        $uuid = $this->UUIDv4Object->getUUID();

        $response = $this->guzzleClient->request(
            'POST',
            self::API_URL . "/users/" . $uuid . "/skipped_payout",
            [
                'amount_in_decimal' => $amount_skipped
            ]
        );

        if ($response->getStatusCode() == 200) {
            return $this->getTotalBalance();
        }

        return false;
    }

    /**
     * Get the total amount from previously skipped payouts
     * @return Decimal
     */
    public function getSkippedPayouts(): Decimal
    {
        $uuid = $this->UUIDv4Object->getUUID();

        try {
            $response = $this->guzzleClient->request('GET', self::API_URL . "/users/" . $uuid . "/skipped_payout");

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());

                $totalAmount = new Decimal("0");
                foreach ($data->skipped_payouts as $payout) {
                    $totalAmount += $payout->amount_in_decimal;
                }
                return $totalAmount;
            }
        } catch (GuzzleException $e) {
        }
        return new Decimal("0.0");
    }

    /**
     * Clear the skipped payouts, this is for after the payout has been added to user balance
     *
     * @return bool
     * @throws GuzzleException
     */
    public function resetSkippedPayouts(): bool
    {
        $uuid = $this->UUIDv4Object->getUUID();

        $response = $this->guzzleClient->request(
            'POST',
            self::API_URL . "/users/" . $uuid . "/skipped_payout",
            [
                'reset' => true
            ]
        );

        if ($response->getStatusCode() == 200) {
            return true;
        }

        return false;
    }

    /**
     * Store a transaction record as a log that the process has been executed for the user
     *
     * @return bool
     * @throws GuzzleException
     */
    public function storeTransaction()
    {
        $uuid = $this->UUIDv4Object->getUUID();

        $response = $this->guzzleClient->request(
            'POST',
            self::API_URL . "/users/" . $uuid . "/transaction",
            [
                'date_time' => new DateTime(),
                'concluded' => true,
                'uuid' => $uuid
            ]
        );

        if ($response->getStatusCode() == 200) {
            return true;
        }

        return false;
    }

    /**
     * Get the last datetime that a successful payout was done for the user
     * @return DateTime
     */
    public function getLastPayout(): DateTime
    {
        $statement = $this->getUserStatement();
        foreach ($statement as $transaction) {
            if ($transaction->type == "payout") {
                return $transaction->date_time;
            }
        }
        return new DateTime();
    }

    /**
     * Get list of transactions as statement. Data in DESC order.
     *
     * @return array of transaction object
     * @todo This is an assumed new implementation, not yet part of the Finance API Contract
     *
     */
    public function getUserStatement(): array
    {
        $uuid = $this->UUIDv4Object->getUUID();
        $UUID = null;
        $transactions = [];
        try {
            $response = $this->guzzleClient->request(
                'GET',
                self::API_URL . "/users/" . $uuid . "/transactions?sort=DESC"
            );

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());

                $transactions = $data->transactions;
            }
        } catch (GuzzleException $e) {
        }

        $this->userStats->setStatement($transactions);
        return $this->userStats->getStatement();
    }

    /**
     * Get basic stats as provided by existing Finance API Contract
     * @return UserStatsObject with Monthly Income if available
     */
    private function getUserData(): UserStatsObject
    {
        $uuid = $this->UUIDv4Object->getUUID();
        $UUID = null;
        $active = false;
        $monthly_income = 0;
        try {
            $UUID = new UUIDv4Object($uuid);

            $response = $this->guzzleClient->request('GET', self::API_URL . "/users/" . $uuid);

            if ($response->getStatusCode() == 200) {
                $data = json_decode($response->getBody());

                $monthly_income = $data->income;
                $active = $data->id == $uuid;
            }

            return new UserStatsObject($UUID, $monthly_income, $active);
        } catch (GuzzleException $e) {
            return new UserStatsObject($UUID, $monthly_income, $active);
        } catch (UuidException $e) {
            die("Fatal error");
        }
    }
}
