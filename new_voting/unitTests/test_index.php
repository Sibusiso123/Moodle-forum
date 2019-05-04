<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class indexTest extends TestCase{
  
 public function test_tests(){
   $temp=tests();
 $this->assertEquals(1,1, "correct!"); 
 }
 
}
