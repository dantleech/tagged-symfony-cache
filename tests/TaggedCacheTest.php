<?php

namespace DTL\TaggedHttpCache\Tests;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use DTL\TaggedHttpCache\Tests\Kernel\DummyKernel;
use DTL\TaggedHttpCache\TaggedCache;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Filesystem\Filesystem;

class TaggedCacheTest extends TaggedCacheTestCase
{
    public function testCache()
    {
        $request = Request::create('https://www.dantleech.com/foobar');
        $response = $this->cacheKernel->handle($request);
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * It should create symlinks to cached pages when the response is tagged.
     */
    public function testReponseTagCreateSymlinks()
    {
        $request = Request::create('https://www.dantleech.com/foobar');
        $this->testKernel->response->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one', 'tag_two')));
        $this->cacheKernel->handle($request);

        $this->assertCount(1, $this->manager->getPathsForTag('tag_one'));
        $this->assertCount(1, $this->manager->getPathsForTag('tag_two'));

        $request = Request::create('https://www.dantleech.com/foobar/barfoo');
        $this->testKernel->response = $this->createCachedResponse('hello');
        $this->testKernel->response->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one')));
        $this->cacheKernel->handle($request);

        $this->assertCount(2, $this->manager->getPathsForTag('tag_one'));
        $this->assertCount(1, $this->manager->getPathsForTag('tag_two'));
    }

    /**
     * It should purge a given tag.
     */
    public function testResponseTagPurge()
    {
        $request = Request::create('https://www.dantleech.com/foobar');
        $this->testKernel->response->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one', 'tag_two')));
        $this->cacheKernel->handle($request);
        $this->assertCount(1, $this->manager->getPathsForTag('tag_one'));

        $request = Request::create('https://www.dantleech.com/foobar');
        $request->headers->set(TaggedCache::HEADER_DO_PURGE, 'yes');
        $request->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one', 'tag_two')));
        $this->cacheKernel->handle($request);
    }
}
