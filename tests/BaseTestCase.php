<?php

namespace DTL\TaggedHttpCache\Tests;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use DTL\TaggedHttpCache\Tests\Kernel\DummyKernel;
use DTL\TaggedHttpCache\TaggedCache;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\Filesystem\Filesystem;

abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    protected $testKernel;
    protected $cacheKernel;
    protected $filesystem;

    public function setUp()
    {
        $this->filesystem = new Filesystem();
        $this->testKernel = new DummyKernel($this->createCachedResponse('Test'));
        $this->testKernel->response->setMaxAge(600);
        $this->testKernel->response->setSharedMaxAge(600);
        $store = new Store(__DIR__.'/cache');
        $cache = new HttpCache($this->testKernel, $store);
        $this->cacheKernel = new TaggedCache($cache, $store, __DIR__.'/cache/tags');
    }

    public function tearDown()
    {
        $this->filesystem->remove(__DIR__.'/cache');
    }

    protected function createCachedResponse($body)
    {
        $response = new Response($body);
        $response->setMaxAge(600);
        $response->setSharedMaxAge(600);

        return $response;
    }
}
