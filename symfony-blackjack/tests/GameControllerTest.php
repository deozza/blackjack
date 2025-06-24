<?php

use PHPUnit\Framework\TestCase;

class GameControllerTest extends TestCase
{
    public function testGameHello()
    {
        $hello = "hello";
        $this->assertEquals("hello", $hello);
    }
}
