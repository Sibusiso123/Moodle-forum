<?php

include_once __DIR__ .'/../../../tests/config.php';
class DatabaseTest extends PHPUnit\Framework\TestCase
{
    private $host = 'localhost';
    private $name = 'voting';
    private $user = 'root';
    private $user_='admin';
    private $pass = '';
    
    /**
     * @covers Database::connect
     */
    public function testConnect()
    {
        $database = new Database($this->host, $this->name, $this->user, $this->pass);
        $this->assertNotEmpty($database->connect());
    }
    public function testFailConnect()
    {
        $database = new Database($this->host,$this->name,$this->user_,$this->pass);
        $conn=$database->connect();
        if($conn instanceof \Exception){
            throw $conn;
        }
        $this->assertNull($conn);
    }
}
