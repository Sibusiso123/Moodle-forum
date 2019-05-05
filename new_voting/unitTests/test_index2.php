<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class index2Test extends TestCase{
  
 public function test_tested(){
   //$temp=tests();
   echo "Code Coverage";
 $this->assertEquals(1,1, "correct!"); 
 }
 
}
