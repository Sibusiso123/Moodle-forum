<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class indexTest extends TestCase{
  
 public function test_tests(){
   //$temp=tests();
   echo "Just a test!!";
 $this->assertEquals(1,1, "correct!"); 
 }
 
}
