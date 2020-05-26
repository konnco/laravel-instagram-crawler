<?php

namespace Konnco\InstagramCrawler\Tests;

use Konnco\InstagramCrawler\InstagramCrawler as Crawler;

class InstagramCrawlerTest extends \Orchestra\Testbench\TestCase
{
    /** @test */
    public function testGetMediaByUsername(): void
    {
        $crawler = new Crawler;
        $media = $crawler->getMediaByUser('instagram')->returnSimpleResult();

        $this->assertIsArray($media);
    }

    /** @test */
    public function testGetMediaByTag(): void
    {
        $crawler = new Crawler;
        $media = $crawler->getMediaByTag('instagram')->returnSimpleResult();

        $this->assertIsArray($media);
    }

    /** @test */
    public function testGetMediaByUsernameAndTag(): void
    {
        $crawler = new Crawler;
        $media = $crawler->getMediaByTag('instagram')->returnSimpleResult();
        $username = 'instagram';
        $media = collect($media)->filter(function($value) use ($username) {
            return $value['username'] === $username;
        })->toArray();

        $this->assertIsArray($media);
    }
}
