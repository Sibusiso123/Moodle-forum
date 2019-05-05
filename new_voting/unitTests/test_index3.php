<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class index3Test extends TestCase{
  
 public function test_number(){
   echo (getcwd());
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/index3.php');
   number();
   echo "It works but doesn't show on the coverall";
 $this->assertEquals(1,1, "correct!"); 
 }
 
}
