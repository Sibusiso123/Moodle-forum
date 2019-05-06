<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class indexTest extends TestCase{
  
 public function test_tests(){
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/index.php');
   $test=tests(8,3);
 $this->assertEquals(11,$test, "correct!"); 
 }
 
}
