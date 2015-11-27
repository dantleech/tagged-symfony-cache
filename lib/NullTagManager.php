<?php

namespace DTL\TaggedHttpCache;

class NullTagManager implements TagManagerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPathsForTag($tag)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function invalidateTags(array $tags)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createTag($tag, $contentDigest)
    {
    }
}
