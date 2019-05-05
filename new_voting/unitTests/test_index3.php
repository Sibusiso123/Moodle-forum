<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class index3Test extends TestCase{
  
 public function test_number(){
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/index3.php');
   $test=number(2,3);
   echo "It works but doesn't show on the coverall";
 $this->assertEquals(5,$test, "correct!"); 
 }
 
}
