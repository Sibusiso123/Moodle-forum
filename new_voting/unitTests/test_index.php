<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class indexTest extends TestCase{
  
 public function test_tests(){
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/index.php');
   $test=tests(5,5);
 $this->assertEquals(10,$test, "correct!"); 
 }
 
}
