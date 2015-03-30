<?php
namespace ITE;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-10 at 14:53:39.
 * @group ite
 * @group cache
 */
class cacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Error handler trait.
     */
    use errorHandler;
    
    /**
     * @var cache
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {   
        $this->initErrorHandler(true);
        $_ITE = ite::singleton();
        $this->object = new cache($_ITE);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $output = $this->getErrors();
                
        if($output !== false){
            echo $output;
        }
    }

    /**
     * @covers ITE\cache->get_file
     */
    public function testGet_file()
    {
        $this->object->get_file('dummy_file.php');
        $this->assertError('Imposible conectar al servidor FTP remoto.', E_USER_ERROR);
    }

    /**
     * @covers ITE\cache::compress_cache_file
     * @todo   Implement testCompress_cache_file().
     */
    public function testCompress_cache_file()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ITE\cache::cache_status
     * @todo   Implement testCache_status().
     */
    public function testCache_status()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @covers ITE\cache::cache
     * @todo   Implement testCache().
     */
    public function testCache()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
