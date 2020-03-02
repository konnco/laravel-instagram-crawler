<?php

namespace Konnco\InstagramCrawler\Tests;

use Konnco\InstagramCrawler\InstagramCrawler as Crawler;

class InstagramCrawlerTest extends \Orchestra\Testbench\TestCase
{
    /** @test */
    public function testGetMediaByUsername(): void
    {
        $crawler = new Crawler;
        $media = $crawler->getMediaByUser('ijalnst')->returnSimpleResult();

        $this->assertIsArray($media);
    }

    /** @test */
    public function testGetMediaByTag(): void
    {
        $crawler = new Crawler;
        $media = $crawler->getMediaByTag('Instanusantaradiy_701')->returnSimpleResult();

        $this->assertIsArray($media);
    }

    /** @test */
    public function testGetMediaByUsernameAndTag(): void
    {
        $crawler = new Crawler;
        $media = $crawler->getMediaByTag('Instanusantaradiy_701')->returnSimpleResult();
        $username = 'ijalnst';
        $media = collect($media)->filter(function($value) use ($username) {
            return $value['username'] === $username;
        })->toArray();

        $this->assertIsArray($media);
    }
}
