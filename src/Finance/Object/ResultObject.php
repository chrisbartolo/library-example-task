<?php

namespace Finance\IA\Object;

/**
 * A standardised messaging result object for easy manipulation and re-use
 * @package Finance\IA\Object
 */
class ResultObject
{
    public bool $success;
    public int $error_code;
    public string $message;
    public array $data = array();

    public function __construct()
    {
    }

    /**
     * @param int $error_code
     */
    public function setErrorCode(int $error_code): void
    {
        $this->error_code = $error_code;
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
}

?>