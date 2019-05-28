<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class serverTest extends TestCase{
  
 public function test_test(){
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/server.php');
   $test=number(13,13);
 $this->assertEquals(13,$test, "correct!");
 //$this->assertEquals(13,13, "correct!"); 
 }
  public function test_getLikes(){
    require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/server.php');
    $test1=getlikes(1,1);
 $this->assertEquals(1,$test1, "correct!"); 
 }
}
