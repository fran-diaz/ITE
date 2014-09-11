<?php
namespace ITE;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2014-09-09 at 13:42:24.
 * @group ite
 * @group ite_main
 */
class iteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Error handler trait.
     */
    use errorHandler;
    
    /**
     * @var ite
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->initErrorHandler(true);
        $this->object = ite::singleton('dummy');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers ITE\ite::singleton
     */
    public function testSingleton()
    {
        $this->assertInstanceOf(__NAMESPACE__.'\\ite',$this->object);
    }

    /**
     * @covers ITE\ite->set_db_controller
     */
    public function testSet_db_controller()
    {
        $this->assertTrue($this->object->set_db_controller('mysql'));
        $this->assertInstanceOf(__NAMESPACE__.'\\mysql',$this->object->bdd);
        $this->assertFalse($this->object->set_db_controller('dummy'));
    }

    /**
     * @covers ITE\ite->__cache
     */
    public function test__cache()
    {
        $this->assertFalse($this->object->__cache());
        $this->assertError("Imposible conectar al servidor FTP remoto.",E_USER_ERROR);
    }

    /**
     * @covers ITE\ite->request
     */
    public function testRequest()
    {
        $_ITE = $this->object;
        $uri_ptr = "blog/{date}/{uri}";
        $callback = function($date,$uri) use ($_ITE){
            return true;
        };
        
        $_GET['url'] = 'dummy.html';
        $this->assertFalse($this->object->request($uri_ptr, $callback));
        $_GET['url'] = 'blog/2014-09-11/dummy title';
        $this->assertTrue($this->object->request($uri_ptr, $callback));
    }

    /**
     * @covers ITE\ite->__debug
     */
    public function test__debug()
    {
        $this->assertFalse($this->object->__debug());
    }

    /**
     * @covers ITE\ite->__error
     */
    public function test__error()
    {
        $this->object->__error('Dummy text.');
        $this->assertError('Dummy text.',E_USER_ERROR);
    }

    /**
     * @covers ITE\ite->__warn
     */
    public function test__warn()
    {
        $this->object->__warn('Dummy text.');
        $this->assertError('Dummy text.',E_USER_WARNING);
    }

    /**
     * @covers ITE\ite::__info
     */
    public function test__info()
    {
        $this->object->__info('Dummy text.');
        $this->assertError('Dummy text.',E_USER_NOTICE);
    }
}
