<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
class index3Test extends TestCase{
  
 public function test_number(){
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/index3.php');
   $test=number(5,0);
 $this->assertEquals(5,$test, "correct!"); 
 }
 
}
