<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class UserTest extends TestCase{
  
 public function test_user(){
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/User.php');
   $test=user(12,8,3,24);
 $this->assertEquals(32,5,$test, "correct!"); 
 }
 
}
