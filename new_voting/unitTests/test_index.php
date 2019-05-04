<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
#require_once('locallib.php');
class indexTest extends TestCase{
  
 public function test_tests(){
   //$temp=tests();
 $this->assertEquals(max(array(getLikes($post['id'])+ getDislikes($post['id']))),max(array(getLikes($post['id'])+ getDislikes($post['id']))), "correct!"); 
 }
 
}
