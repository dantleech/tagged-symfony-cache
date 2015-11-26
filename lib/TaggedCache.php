<?php

namespace DTL\TaggedHttpCache;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\HeaderBag;

class TaggedCache implements HttpKernelInterface
{
    const HEADER_TAGS = 'X-TaggedCache-Tags';
    const HEADER_CONTENT_DIGEST = 'X-Content-Digest';
    const METHOD_PURGE_TAG = 'PURGE_TAG';

    private $kernel;
    private $store;
    private $baseTagPath;
    private $filesystem;

    public function __construct(HttpKernelInterface $kernel, Store $store, $baseTagPath)
    {
        $this->kernel = $kernel;
        $this->store = $store;
        $this->baseTagPath = $baseTagPath;
        $this->filesystem = new Filesystem();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if ($request->getMethod() == self::METHOD_PURGE_TAG) {
            $this->purgeTags($request);
        }

        $response = $this->kernel->handle($request, $type, $catch);

        if (!$response->headers->has(self::HEADER_TAGS)) {
            return;
        }

        $this->handleTags($response);
    }

    /**
     * Return the concrete cache paths for the given tag.
     *
     * @param string $tag
     * @return string[]
     */
    public function getPathsForTag($tag)
    {
        $tagPath = $this->getTagPath($tag);

        if (!file_exists($tagPath)) {
            return array();
        }

        $filenames = scandir($tagPath);
        $paths = array();

        foreach ($filenames as $filename) {
            if (in_array($filename, array('.', '..'))) {
                continue;
            }

            $paths[] = realpath($tagPath . '/' . $filename);
        }

        return $paths;
    }

    private function purgeTags(Request $request)
    {
        $tags = $this->getTagsFromHeaders($request->headers);

        foreach ($tags as $tag) {
            $tagPath = $this->getTagPath($tag);
            $paths = $this->getPathsForTag($tag);

            // remove cache entries
            foreach ($paths as $path) {
                $this->filesystem->remove($path);
            }

            // remove the tag directory
            $this->filesystem->remove($tagPath);
        }
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
            $this->createTag($tag, $contentDigest);
        }
    }

    private function createTag($tag, $contentDigest)
    {
        $tagPath = $this->getTagPath($tag);

        if (false === file_exists($tagPath)) {
            $this->filesystem->mkdir($tagPath);
        }

        $symlinkDest = $tagPath . '/' . $contentDigest;
        $symlinkOrig = $this->store->getPath($contentDigest);

        if (file_exists($symlinkDest)) {
            $this->filesystem->remove($symlinkDest);
        }

        $this->filesystem->symlink($symlinkOrig, $symlinkDest);
    }

    private function escapeTag($tag)
    {
        $tag = preg_replace('/[^A-Za-z0-9_\-]/', '_', $tag);
        return $tag;
    }

    private function getTagPath($tag)
    {
        return $this->baseTagPath . '/' . $this->escapeTag($tag);
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
        $tags = json_decode($tagsRaw);

        if (false === $tags) {
            throw new \RuntimeException(sprintf(
                'Could not JSON decode tags header with value "%s"',
                $tagsRaw
            ));
        }

        return $tags;
    }
}
