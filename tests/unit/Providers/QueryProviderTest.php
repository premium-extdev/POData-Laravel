<?php

namespace AlgoWeb\PODataLaravel\Providers;

use AlgoWeb\PODataLaravel\Models\TestCase as TestCase;
use AlgoWeb\PODataLaravel\Query\LaravelQuery;
use Illuminate\Support\Facades\App;

/**
 * Generated Test Class.
 */
class QueryProviderTest extends TestCase
{
    /**
     * @var \AlgoWeb\PODataLaravel\Providers\QueryProvider
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
//        $this->object = new \AlgoWeb\PODataLaravel\Providers\QueryProvider();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\QueryProvider::boot
     * @todo   Implement testBoot().
     */
    public function testBoot()
    {
        $foo = new QueryProvider($this->app);
        $foo->boot();
    }

    /**
     * @covers \AlgoWeb\PODataLaravel\Providers\QueryProvider::register
     * @todo   Implement testRegister().
     */
    public function testRegister()
    {
        $foo = new QueryProvider($this->app);
        $foo->register();

        $result = App::make('odataquery');
        $this->assertTrue($result instanceof LaravelQuery);
    }
}
