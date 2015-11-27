<?php

namespace DTL\TaggedHttpCache\Tests;

use Symfony\Component\HttpFoundation\Request;
use DTL\TaggedHttpCache\TaggedCache;
use DTL\TaggedHttpCache\TaggedCachePurger;

/**
 * Note that the PHP web server must be running for this test:
 *
 * php -S localhost:8000 tests/App/web.php
 */
class TaggedCachePurgerTest extends TaggedCacheTestCase
{
    public function testPurger()
    {
        $request = Request::create('/foobar/barfoo');
        $this->testKernel->response = $this->createCachedResponse('one');
        $this->testKernel->response->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one')));
        $this->cacheKernel->handle($request);
        $this->assertCount(1, $this->manager->getPathsForTag('tag_one'));

        $request = Request::create('/');
        $this->testKernel->response = $this->createCachedResponse('two');
        $this->testKernel->response->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('tag_one')));
        $this->cacheKernel->handle($request);
        $this->assertCount(2, $this->manager->getPathsForTag('tag_one'));

        $purger = new TaggedCachePurger(array('http://localhost:8000'));
        $return = $purger->invalidate(array('tag_one'));
        $this->assertEquals('PURGED', $return['Status']);
        $this->assertEquals(2, $return['NbCacheEntries']);
        $this->assertCount(0, $this->manager->getPathsForTag('tag_one'));
    }
}
