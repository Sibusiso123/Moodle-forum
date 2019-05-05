<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class checkTest extends TestCase{
  
 public function test_check(){
   require('check.php');
   $this->expectOutputString('3');
} 

}
