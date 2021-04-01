<?php
namespace Finance\IA\Test\ObjectTest;

use Finance\IA\Exception\UuidException;
use Finance\IA\Object\UUIDv4Object;
use PHPUnit\Framework\TestCase;

class UUIDv4ObjectTest extends TestCase
{

    public function testCanBeCreatedFromValidUUID(): void
    {
        $uuidExample = "88224979-406e-4e32-9458-55836e4e1f95";

        try {
            $UUIDv4Object = new UUIDv4Object($uuidExample);
            $this->assertEquals($uuidExample, $UUIDv4Object->getUUID());
        } catch (\Exception $e) {
            $this->throwException($e);
        }
    }

    public function testCanNotBeCreatedFromInvalidUUID(): void
    {
        $this->expectException(UuidException::class);
        $uuidExample = "88224979406e-4e32-9458-55836e4e1f95";

        $UUIDv4Object = new UUIDv4Object($uuidExample);
    }
}
