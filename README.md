Tagged (Symfony) Http Cache
===========================

This is an experimental middleware that wraps the Symfony HTTP cache and
allows invalidation by tags. This allows you to effectively cache your content
until it is specifically invalidated.

For example: You have a page which shows content from your database with IDs
`12` and `6`. When you display the page you send a header in the response with
those IDs (the tags). The Middleware will listen for these headers and
reference them with the cached page.

Now, when you save your content (i.e. the content with IDs `12` or `6`) you
must invalidate these tags via. the `TagManager` service in this library, and
then the cache will be refreshed only for pages with those tags.

Usage
-----

The following assumes you have only one server (and so can access the cache
files directly).

The first step is to modify you AppKernel so that we can inject the
`TagManager` later on:

```php
<?php

// ...
use DTL\TaggedHttpCache\TagManagerInterface;
use DTL\TaggedHttpCache\NullTagManager;

class AppKernel extends Kernel
{
    private $tagManager;

    public function __construct($environment, $debug, TagManagerInterface $tagManager = null)
    {
        $this->tagManager = $tagManager ?: new NullTagManager();
        parent::__construct($environment, $debug);
    }

    public function getTagManager()
    {
        return $this->tagManager;
    }

    protected function buildContainer()
    {
        $container = parent::buildContainer();
        $definition = new Definition('DTL\TaggedHttpCache\TagManager');
        $definition->setFactory(array(
            new Reference('kernel'),
            'getTagManager'
        ));
        $container->setDefinition('tagged_http_cache.tag_manager', $definition);

        return $container;
    }
```

Now change you `web/app.php` as follows:

```php
<?php
// AppKernel.php

// ...
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use DTL\TaggedHttpCache\TaggedCache;
use DTL\TaggedHttpCache\TagManager;

// create the standard Symfony cache store
$store = new Store(__DIR__ . '/../app/cache/http_cache');

// create the tag manager
$tagManager = new TagManager($store, __DIR__ . '/../app/cache/http_cache_tags');

// pass the tag manager to the kernel
$appKernel = new AppKernel('prod', false, $tagManager);
$appKernel->loadClassCache();

// instantiate the Symfony HTTP cache
$kernel = new HttpCache($appKernel, $store);

// wrap the Symfony HTTP cache with the TaggedCache
$kernel = new TaggedCache($kernel, $tagManager);

// ...
```

To use the cache you will need to send the tag headers in the response:

```php

class MyController
{
    public function myAction(Request $request)
    {
        $response = // you somehow have the Response object
        $response->headers->set(TaggedCache::HEADER_TAGS, json_encode(array('one', 'two')));
        $response->setPublic();

        return $response;
    }
}
```

Now you will need to invalidate when you update your content, in the first
stage you hacked the kernel so you should now have the
`tagged_http_cache.tag_manager` service. You can invalidate certain tags as
follows:

```php
<?php

$container->get('tagged_http_cache.tag_manager')->invalidateTags(array('one', 'two'));
```
