<?php

namespace Konnco\InstagramCrawler\Factory;

use Konnco\InstagramCrawler\Model\Tag;

class TagFactory
{
    public static function create(string $name, int $count = 0): Tag
    {
        return new Tag($name, $count);
    }
}
