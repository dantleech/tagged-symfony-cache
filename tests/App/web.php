<?php

use DTL\TaggedHttpCache\Tests\Kernel\DummyKernel;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use DTL\TaggedHttpCache\TaggedCache;
use Symfony\Component\HttpFoundation\Request;
use DTL\TaggedHttpCache\TagManager;

require_once __DIR__.'/../../vendor/autoload.php';

$app = new DummyKernel();
$store = new Store(__DIR__.'/../cache');
$manager = new TagManager($store, __DIR__ . '/../cache/tags');
$httpCache = new HttpCache($app, $store);
$taggedCache = new TaggedCache($httpCache, $manager);

$request = Request::createFromGlobals();
$response = $taggedCache->handle($request);
$response->send();
