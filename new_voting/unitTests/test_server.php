<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class serverTest extends TestCase{
  
 public function test_test(){
  // $temp=test();
 $this->assertEquals(1,1, "correct!"); 
 }
 
}
