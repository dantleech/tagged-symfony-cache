<?php

use DTL\TaggedHttpCache\Tests\Kernel\DummyKernel;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use DTL\TaggedHttpCache\TaggedCache;
use Symfony\Component\HttpFoundation\Request;

require_once __DIR__.'/../../vendor/autoload.php';

$app = new DummyKernel();
$store = new Store(__DIR__.'/../cache');
$httpCache = new HttpCache($app, $store);
$taggedCache = new TaggedCache($httpCache, $store, __DIR__. '/../cache/tags');

$request = Request::createFromGlobals();
$response = $taggedCache->handle($request);
$response->send();
