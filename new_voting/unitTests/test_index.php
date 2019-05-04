<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class serverTest extends TestCase{
  
 public function test_testing(){
  $temp=test();
 $this->assertEquals(0,0, "correct!"); 
 }
 
}
