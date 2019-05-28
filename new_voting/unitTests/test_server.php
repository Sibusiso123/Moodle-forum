<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class serverTest extends TestCase{
  
 public function test_test(){
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/server.php');
   $test=number(13,10);
 $this->assertEquals(23,$test, "correct!");
 //$this->assertEquals(13,13, "correct!"); 
 }
  public function test_getLikes(){
    require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/server.php');
    $test=getlikes(1,2);
 $this->assertEquals(3,$test, "correct!"); 
 }
    public function test_getDislikes(){
    require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/server.php');
    $test=getlikes(1,2);
 $this->assertEquals(2,$test, "correct!"); 
 }
}
