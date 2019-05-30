<?php
    
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('config.php');
class index3Test extends TestCase{
    
  
 public function test_tests(){
     
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/index3.php');  
    
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
}   
