<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class locallibTest extends TestCase{
  
 public function test_test_moodleforum_disallow_subscribe_on_create(){
   require_once('/home/travis/build/hex-hypercity/Moodle-forum/moodleforum/tests/locallib.php');
   $test=test_moodleforum_disallow_subscribe_on_create(1,1);
 $this->assertEquals(1,$test, "correct!"); 
 }
 
}
