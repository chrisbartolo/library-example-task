<?php

namespace Finance\IA\Object;

use Finance\IA\Config\ErrorCodes;

/**
 * A standardised messaging result object for easy manipulation and re-use
 * @package Finance\IA\Object
 */
class ResultObject
{
    public const RESULT_NONE = 0;
    public const RESULT_INTEREST_ZERO = 1;
    public const RESULT_INTEREST_FAILED = 2;
    public const RESULT_PAYOUT_COUNT_FAILED = 3;
    public const RESULT_ACCOUNT_IS_ACTIVATED = 4;
    public const RESULT_ACCOUNT_NOT_ACTIVATED = 5;
    public const RESULT_DEPOSIT_AMOUNT_MIN_LIMIT = 6;
    public const RESULT_OPENED_AND_ACTIVE = 7;
    public const RESULT_DEPOSIT_PAYOUT_LESS_MINIMUM = 8;

    public bool $success;
    public int $errorCode;
    public string $message;
    public array $data = array();

    public function __construct()
    {
    }

    public function setResult(bool $success, int $errorCode, string $message, array $data): void
    {
        $this->setSuccess($success);
        $this->setErrorCode($errorCode);
        $this->setMessage($message);
        $this->setData($data);
    }

    /**
     * @param int $errorCode
     */
    public function setErrorCode(int $errorCode): void
    {
        $this->errorCode = $errorCode;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }

    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function clearResult(): void
    {
        $this->message = "";
        $this->success = 1;
        $this->data = array();
        $this->errorCode = ErrorCodes::NONE;
    }


    public function returnResult($type): ResultObject
    {
        $resultObject = new ResultObject();
        switch ($type)
        {
            case self::RESULT_INTEREST_ZERO:
                $resultObject->setResult(1, ErrorCodes::NONE, "No interest has been accumulated.", []); break;
            case self::RESULT_INTEREST_FAILED:
                $resultObject->setResult(0, ErrorCodes::INTEREST_ASSIGN, "Interest calculation error", []); break;
            case self::RESULT_PAYOUT_COUNT_FAILED:
                $resultObject->setResult(1, ErrorCodes::NONE, "No payout due.", []); break;
            case self::RESULT_ACCOUNT_IS_ACTIVATED:
                $resultObject->setResult(1, ErrorCodes::NONE, "Account has been activated.", []); break;
            case $resultObject::RESULT_ACCOUNT_NOT_ACTIVATED:
                $resultObject->setResult(0, ErrorCodes::ACCOUNT_NOT_SET, "Unable to open user account", []); break;
            case $resultObject::RESULT_DEPOSIT_AMOUNT_MIN_LIMIT:
                $resultObject->setResult(0, ErrorCodes::DEPOSIT_INVALID, "Deposit amount must be more than 0", []); break;
            case $resultObject::RESULT_OPENED_AND_ACTIVE:
                $resultObject->setResult(1, ErrorCodes::NONE, "Interest Account is opened and active", []); break;
            case $resultObject::RESULT_DEPOSIT_PAYOUT_LESS_MINIMUM:
                $resultObject->setResult(1, ErrorCodes::NONE, "Interest accumulated is less than 1 penny. It will be included in the next payout which totals to at least 1 penny.", []); break;

        }
        return $resultObject;
    }
}

?>