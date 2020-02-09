<?php

declare(strict_types=1);

namespace Konnco\InstagramCrawler\Factory;

use Konnco\InstagramCrawler\Model\User;
use Konnco\InstagramCrawler\Model\Location;
use Konnco\InstagramCrawler\Model\Photo;
use Konnco\InstagramCrawler\Model\Video;
use Konnco\InstagramCrawler\Model\Dimension;
use Konnco\InstagramCrawler\Model\Tag;

class MediaFactory
{
    /**
     * @param int      $id
     * @param string   $code
     * @param string   $url
     * @param array    $dimension
     * @param int      $created
     * @param User     $user
     * @param int      $likes
     * @param int      $comments
     * @param bool     $ad
     * @param mixed    $caption
     * @param Location $location
     *
     * @return Photo
     */
    public static function createPhoto(
        int $id,
        string $code,
        string $url,
        array $dimension,
        int $created,
        User $user,
        int $likes = 0,
        int $comments = 0,
        bool $ad = false,
        $caption = null,
        Location $location = null
    ): Photo {
        return new Photo(
            $id,
            $code,
            $url,
            new Dimension($dimension['width'], $dimension['height']),
            new \DateTime("@{$created}"),
            $user,
            $caption ? self::extractHashtags($caption) : [],
            $likes,
            $comments,
            $ad,
            $caption,
            $location
        );
    }

    /**
     * @param int      $id
     * @param string   $code
     * @param string   $url
     * @param string   $thumb
     * @param int      $views
     * @param array    $dimension
     * @param int      $created
     * @param User     $user
     * @param int      $likes
     * @param int      $comments
     * @param bool     $ad
     * @param mixed    $caption
     * @param Location $location
     *
     * @return Video
     */
    public static function createVideo(
        int $id,
        string $code,
        string $url,
        string $thumb,
        int $views,
        array $dimension,
        int $created,
        User $user,
        int $likes = 0,
        int $comments = 0,
        bool $ad = false,
        $caption = null,
        Location $location = null
    ): Video {
        return new Video(
            $id,
            $code,
            $url,
            $thumb,
            $views,
            new Dimension($dimension['width'], $dimension['height']),
            new \DateTime("@{$created}"),
            $user,
            $caption ? self::extractHashtags($caption) : [],
            $likes,
            $comments,
            $ad,
            $caption,
            $location
        );
    }

    private static function extractHashtags(string $caption): array
    {
        $tags = [];
        if (preg_match_all('/\S*#((?:\[[^\]]+\]|\S+))/i', $caption, $matches) > 0) {
            $tags = array_map(function ($tag): Tag {
                return TagFactory::create($tag);
            }, array_values(array_unique($matches[1])));
        }

        return $tags;
    }
}
