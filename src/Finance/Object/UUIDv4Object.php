<?php

namespace Finance\IA\Object;

use Finance\IA\Exception\UuidException;

/**
 * Basic object for user id based on UUID v4 requirements
 * @package Finance\IA\Object
 */
class UUIDv4Object
{
    private string $uuid;

    public function __construct(string $uuidv4)
    {
        if ($this->isValid($uuidv4)) {
            $this->setUUID($uuidv4);
        } else {
            throw new UuidException("The user ID that has been provided does not match the UUIDv4 Standard.");
        }
    }

    public function isValid(string $value): bool
    {
        //Reference: https://stackoverflow.com/a/19989922
        $uuidv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        if (!preg_match($uuidv4, $value)) {
            return false;
        }
        return true;
    }

    private function setUUID(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUUID(): string
    {
        return $this->uuid;
    }

}