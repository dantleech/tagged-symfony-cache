<?php

namespace DTL\TaggedHttpCache;

use DTL\TaggedHttpCache\TaggedCache;
use Symfony\Component\HttpFoundation\Request;

class TaggedCachePurger
{
    private $purgeUrl;

    public function __construct($purgeUrl = null)
    {
        if (null === $purgeUrl) {
            $request = Request::createFromGlobals();
            $purgeUrl = $request->getPathInfo();
        }

        $this->purgeUrl = $purgeUrl;
    }

    public function invalidate(array $tags)
    {
        $request = array(
            'http' =>
            array(
                'method'  => Request::METHOD_GET,
                'header' => implode("\n", array(
                    sprintf('%s: %s', TaggedCache::HEADER_TAGS, json_encode($tags)),
                    TaggedCache::HEADER_DO_PURGE . ': yes'
                )
            )
        ));
        $context = stream_context_create($request);
        $contents = file_get_contents($this->purgeUrl, false, $context);

        $return = json_decode($contents, true);

        if (!$return) {
            throw new \RuntimeException(sprintf(
                'Could not decode JSON response from HTTP cache: "%s"',
                $contents
            ));
        }

        return $return;
    }
}
