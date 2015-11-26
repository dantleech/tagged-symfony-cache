<?php

namespace DTL\TaggedHttpCache\Tests;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use DTL\TaggedHttpCache\Tests\Kernel\DummyKernel;
use DTL\TaggedHttpCache\TaggedCache;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;

class TaggedCacheTest extends \PHPUnit_Framework_TestCase
{
    private $testKernel;
    private $cacheKernel;
    private $filesystem;

    public function setUp()
    {
        $this->filesystem = new Filesystem();
        $this->testKernel = new DummyKernel($this->createCachedResponse());
        $this->testKernel->response->setMaxAge(600);
        $this->testKernel->response->setSharedMaxAge(600);
        $store = new Store(__DIR__ . '/cache');
        $cache = new HttpCache($this->testKernel, $store);
        $this->cacheKernel = new TaggedCache($cache, $store, __DIR__ . '/cache/tags');
    }

    public function tearDown()
    {
        $this->filesystem->remove(__DIR__ . '/cache');
    }

    public function testCache()
    {
        $request = Request::create('https://www.dantleech.com/foobar');
        $this->cacheKernel->handle($request);
    }

    /**
     * It should create symlinks to cached pages when the response is tagged.
     */
    public function testReponseTagCreateSymlinks()
    {
        $request = Request::create('https://www.dantleech.com/foobar');
        $this->testKernel->response->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one', 'tag_two')));
        $this->cacheKernel->handle($request);

        $this->assertCount(1, $this->cacheKernel->getPathsForTag('tag_one'));
        $this->assertCount(1, $this->cacheKernel->getPathsForTag('tag_two'));

        $request = Request::create('https://www.dantleech.com/foobar/barfoo');
        $this->testKernel->response = $this->createCachedResponse();
        $this->testKernel->response->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one')));
        $this->cacheKernel->handle($request);

        $this->assertCount(2, $this->cacheKernel->getPathsForTag('tag_one'));
        $this->assertCount(1, $this->cacheKernel->getPathsForTag('tag_two'));
    }

    /**
     * It should purge a given tag.
     */
    public function testResponseTagPurge()
    {
        $request = Request::create('https://www.dantleech.com/foobar');
        $this->testKernel->response->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one', 'tag_two')));
        $this->cacheKernel->handle($request);
        $this->assertCount(1, $this->cacheKernel->getPathsForTag('tag_one'));

        $request = Request::create('https://www.dantleech.com/foobar', TaggedCache::METHOD_PURGE_TAG);
        $request->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one', 'tag_two')));
        $this->cacheKernel->handle($request);
    }

    private function createCachedResponse()
    {
        $response = new Response();
        $response->setMaxAge(600);
        $response->setSharedMaxAge(600);

        return $response;
    }
}
