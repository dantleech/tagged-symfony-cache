<?php

namespace DTL\TaggedHttpCache;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;

class TaggedCache implements HttpKernelInterface
{
    const HEADER_TAGS = 'X-TaggedCache-Tags';
    const HEADER_CONTENT_DIGEST = 'X-Content-Digest';
    const HEADER_DO_PURGE = 'X-TaggedCache-Purge';

    private $kernel;

    public function __construct(HttpKernelInterface $kernel, TagManagerInterface $tagManager)
    {
        $this->kernel = $kernel;
        $this->tagManager = $tagManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if ($type !== self::MASTER_REQUEST) {
            return $this->kernel->handle($request, $type, $catch);
        }

        // the built-in PHP webserver does not support the PURGE method
        // so we use a custom header instead.
        if ($request->headers->has(self::HEADER_DO_PURGE)) {
            return $this->handleInvalidate($request);
        }

        $response = $this->kernel->handle($request, $type, $catch);

        if (!$response->headers->has(self::HEADER_TAGS)) {
            return $response;
        }

        $this->handleTags($response);

        return $response;
    }

    private function handleInvalidate(Request $request)
    {
        $tags = $this->getTagsFromHeaders($request->headers);
        $nbCacheEntries = $this->tagManager->invalidateTags($tags);

        return new JsonResponse(array(
            'Status' => 'PURGED',
            'NbCacheEntries' => $nbCacheEntries
        ));
    }

    private function handleTags(Response $response)
    {
        if (!$response->headers->has(self::HEADER_CONTENT_DIGEST)) {
            throw new \RuntimeException(sprintf(
                'Expected to find a content digest in the header with key: "%s"',
                self::HEADER_CONTENT_DIGEST
            ));
        }

        $contentDigest = $response->headers->get(self::HEADER_CONTENT_DIGEST);
        $tags = $this->getTagsFromHeaders($response->headers);

        foreach ($tags as $tag) {
            $this->tagManager->createTag($tag, $contentDigest);
        }
    }

    private function getTagsFromHeaders(HeaderBag $headers)
    {
        if (!$headers->has(self::HEADER_TAGS)) {
            throw new \RuntimeException(sprintf(
                'Could not find header "%s"',
                self::HEADER_TAGS
            ));
        }

        $tagsRaw = $headers->get(self::HEADER_TAGS);
        $tags = json_decode($tagsRaw, true);

        if (null === $tags) {
            throw new \RuntimeException(sprintf(
                'Could not JSON decode tags header with value "%s"',
                $tagsRaw
            ));
        }

        return $tags;
    }
}
