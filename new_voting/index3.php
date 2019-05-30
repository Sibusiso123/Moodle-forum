<?php
require_once('/home/travis/build/hex-hypercity/Moodle-forum/new_voting/index3.php');

use PHPUnit\Framework\TestCase;
use PHPUnit\DbUnit\TestCaseTrait;
class index3Test extends TestCase{
    /**
     * @covers downvote
     */
    public function testUpvote()
    {
        $upvote = new Upvote();
        $data =[2,3,4];
        $this->assertNotEmpty($upvote->getAverage($data));
        $this->assertEmpty($upvote->getAverage([]));
    }
    /**
     * @covers upvote
     */
    public function testDownvote()
    {
        $downvote = new Downvote();
        $val =1;
        $this->assertNotEmpty($downvote->get_numeric($val));
        //test fail
        $this->assertEmpty($downvote->get_numeric("a"));
    }
}
?>
