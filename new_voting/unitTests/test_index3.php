<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class index3Test extends TestCase{
  
 public function test_number(){
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/index3.php');
   $test=number(5,100);
 $this->assertEquals(100,$test, "correct!"); 
 }
 
}
