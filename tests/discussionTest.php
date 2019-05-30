<?php

include_once __DIR__ .'../../../discussion.php';
class discussionTest extends PHPUnit\Framework\TestCase
{
    /**
     * @covers downvote
     */
    public function testdownvote()
    {
        $downvote = new Downvote();
        $data =[2,3,4];
        $this->assertNotEmpty($downvote->getAverage($data));
        $this->assertEmpty($downvote->getAverage([]));
    }
    /**
     * @covers upvote
     */
    public function testupvote()
    {
        $upvote = new upvote();
        $val =1;
        $this->assertNotEmpty($upvote->get_numeric($val));
        //test fail
        $this->assertEmpty($upvote->get_numeric("a"));
    }
}
