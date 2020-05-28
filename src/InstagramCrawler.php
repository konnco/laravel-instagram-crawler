<?php

declare(strict_types=1);

namespace Konnco\InstagramCrawler;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\GuzzleException;
use Konnco\InstagramCrawler\Model\Location;
use Konnco\InstagramCrawler\Model\Media;
use Konnco\InstagramCrawler\Model\Tag;
use Konnco\InstagramCrawler\Model\User;
use Konnco\InstagramCrawler\Factory\LocationFactory;
use Konnco\InstagramCrawler\Factory\UserFactory;
use Konnco\InstagramCrawler\Factory\MediaFactory;
use Konnco\InstagramCrawler\Factory\TagFactory;

class InstagramCrawler
{
    const BASE_URI = 'https://www.instagram.com';
    const QUERY = ['__a' => 1];
    const TAG_ENDPOINT = '/explore/tags/%s';
    const LOCATION_ENDPOINT = '/explore/locations/%d';
    const USER_ENDPOINT = '/%s';
    const MEDIA_ENDPOINT = '/p/%s';
    const SEARCH_ENDPOINT = '/web/search/topsearch';
    const SEARCH_CONTEXT_PARAM = 'blended';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var Array
     */
    private $result;

    /**
     * Initializes a new object.
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'query' => self::QUERY,
        ]);
    }

    /**
     * Get a list of recently tagged media.
     *
     * @param string $name The name of the hashtag
     *
     * @return array A list of media
     *
     * @throws GuzzleException
     */
    public function getMediaByTag(string $name): InstagramCrawler
    {
        $response = $this->client->request('GET', sprintf(self::TAG_ENDPOINT, $name));
        $body = json_decode($response->getBody()->getContents(), true);

        $nodeArrays = [];
        foreach ($body['graphql']['hashtag']['edge_hashtag_to_media']['edges'] as $index => $node) {
            $nodeArrays[] = $node['node'];
        }

        if (config('instagram.async', false) === true) {
            return $this->getMediaAsync(array_column($nodeArrays, 'shortcode'));
        }

        return $this->getMedias($nodeArrays);
    }

    /**
     * Get a list of recent media objects from a given location.
     *
     * @param int $id Identification of the location
     *
     * @return array A list of media
     *
     * @throws GuzzleException
     */
    public function getMediaByLocation(int $id): InstagramCrawler
    {
        $response = $this->client->request('GET', sprintf(self::LOCATION_ENDPOINT, $id));
        $body = json_decode($response->getBody()->getContents(), true);

        if (config('instagram.async', false) === true) {
            return $this->getMediaAsync(array_column($body['location']['media']['nodes'], 'code'));
        }

        $nodeArrays = [];
        foreach ($body['graphql']['location']['edge_location_to_media']['edges'] as $index => $node) {
            $nodeArrays[] = $node['node'];
        }

        return $this->getMedias($nodeArrays);
    }

    /**
     * Get the most recent media published by a user.
     *
     * @param string $username The username of a user
     *
     * @return array A list of media
     *
     * @throws GuzzleException
     */
    public function getMediaByUser(string $username): InstagramCrawler
    {
        $response = $this->client->request('GET', sprintf(self::USER_ENDPOINT, $username));
        $body = json_decode($response->getBody()->getContents(), true);

        $nodeArrays = [];
        foreach ($body['graphql']['user']['edge_owner_to_timeline_media']['edges'] as $index => $node) {
            $nodeArrays[] = $node['node'];
        }

        if (config('instagram.async', false) === true) {
            return $this->getMediaAsync(array_column($nodeArrays, 'shortcode'));
        }

        return $this->getMedias($nodeArrays);
    }

    /**
     * Gets media asynchronously.
     *
     * @param array $codes A list of media codes
     *
     * @return array A list of media
     */
    private function getMediaAsync(array $codes): InstagramCrawler
    {
        $promises = array_map(function ($code): PromiseInterface {
            return $this->client->requestAsync('GET', sprintf(self::MEDIA_ENDPOINT, $code));
        }, $codes);
        $results = Promise\settle($promises)->wait();

        $list = [];
        foreach ($results as $r) {
            if ($r['state'] != PromiseInterface::FULFILLED) {
                continue;
            }

            $media = json_decode($r['value']->getBody()->getContents(), true)['graphql']['shortcode_media'];
            $list[] = $this->loadMedia($media);
        }

        $this->result = $list;

        return $this;
    }


    /**
     * Get medias.
     *
     * @param array $medias A list of media
     *
     * @return array A list of media
     */
    private function getMedias(array $medias): InstagramCrawler
    {
        $list = [];
        foreach ($medias as $media) {
            $list[] = $this->loadMedia($media);
        }

        $this->result = $list;

        return $this;
    }

    /**
     * Get information about a media object.
     *
     * @param string $code The code of a media
     *
     * @return Media The media
     *
     * @throws GuzzleException
     */
    public function getMedia(string $code): Media
    {
        $response = $this->client->request('GET', sprintf(self::MEDIA_ENDPOINT, $code));
        $media = json_decode($response->getBody()->getContents(), true)['graphql']['shortcode_media'];

        return $this->loadMedia($media);
    }

    private function loadMedia(array $media): Media
    {
        //var_dump($media['shortcode']);
        $location = null;
        if (array_key_exists('location', $media)) {
            $location = LocationFactory::create(
                (int) $media['location']['id'],
                $media['location']['name'] ?? '',
                $media['location']['slug'] ?? ''
            );
        }
        $user = UserFactory::create(
            (int) $media['owner']['id'],
            $media['owner']['username'] ?? '',
            $media['owner']['profile_pic_url'] ?? '',
            $media['owner']['full_name'] ?? '',
            $media['owner']['is_private'] ?? false
        );
        if ($media['is_video']) {
            return MediaFactory::createVideo(
                (int) $media['id'],
                $media['shortcode'],
                $media['video_url'] ?? '',
                $media['display_url'],
                $media['video_view_count'],
                $media['dimensions'],
                $media['taken_at_timestamp'],
                $user,
                $media['edge_media_preview_like']['count'],
                $media['edge_media_to_comment']['count'] ?? 0,
                $media['is_ad'] ?? false,
                $media['edge_media_to_caption']['edges'][0]['node']['text'] ?? null,
                $location
            );
        }

        return MediaFactory::createPhoto(
            (int) $media['id'],
            $media['shortcode'],
            $media['display_url'],
            $media['dimensions'],
            $media['taken_at_timestamp'],
            $user,
            $media['edge_media_preview_like']['count'],
            $media['edge_media_to_comment']['count'] ?? 0,
            $media['is_ad'] ?? false,
            $media['edge_media_to_caption']['edges'][0]['node']['text'] ?? null,
            $location
        );
    }

    /**
     * Get information about a user.
     *
     * @param string $username The username of a user
     *
     * @return User A user
     *
     * @throws GuzzleException
     */
    public function getUser(string $username): User
    {
        $response = $this->client->request('GET', sprintf(self::USER_ENDPOINT, $username));
        $user = json_decode($response->getBody()->getContents(), true)['graphql']['user'];

        return UserFactory::create(
            (int) $user['id'],
            $user['username'],
            $user['profile_pic_url'],
            $user['full_name'],
            $user['is_private'],
            $user['is_verified'],
            $user['biography'],
            $user['external_url'],
            $user['edge_followed_by']['count'],
            $user['edge_follow']['count'],
            $user['edge_owner_to_timeline_media']['count']
        );
    }

    /**
     * Get information about a location.
     *
     * @param int $id Identification of the location
     *
     * @return Location A location
     *
     * @throws GuzzleException
     */
    public function getLocation(int $id): Location
    {
        $response = $this->client->request('GET', sprintf(self::LOCATION_ENDPOINT, $id));
        $location = json_decode($response->getBody()->getContents(), true)['location'];

        return LocationFactory::create(
            (int) $location['id'],
            $location['name'],
            $location['slug'],
            $location['lat'],
            $location['lng']
        );
    }

    /**
     * Get information about a tag object.
     *
     * @param string $name The name of the hashtag
     *
     * @return Tag A hashtag
     *
     * @throws GuzzleException
     */
    public function getTag(string $name): Tag
    {
        $response = $this->client->request('GET', sprintf(self::TAG_ENDPOINT, $name));
        $tag = json_decode($response->getBody()->getContents(), true)['graphql']['hashtag'];

        return TagFactory::create($tag['name'], $tag['edge_hashtag_to_media']['count']);
    }

    /**
     * Search for hashtags, locations, and users.
     *
     * @param string $query The term to be searched
     *
     * @return array The result of the search
     *
     * @throws GuzzleException
     */
    public function search(string $query): array
    {
        $response = $this->client->request('GET', self::SEARCH_ENDPOINT, [
            'query' => [
                'query' => $query,
                'context' => self::SEARCH_CONTEXT_PARAM,
            ],
        ]);
        $body = json_decode($response->getBody()->getContents(), true);

        return $this->loadSearch($body);
    }

    /**
     * Creates the data structure of a search.
     *
     * @param array $response The search response
     *
     * @return array The result of the search
     */
    private function loadSearch(array $response): array
    {
        $result = ['tags' => [], 'locations' => [], 'users' => []];
        foreach ($response['hashtags'] as $t) {
            $result['tags'][] = new Tag($t['hashtag']['name'], $t['hashtag']['media_count']);
        }
        foreach ($response['places'] as $p) {
            $result['locations'][] = LocationFactory::create(
                $p['place']['location']['pk'],
                $p['place']['title'],
                $p['place']['slug'],
                $p['place']['location']['lat'],
                $p['place']['location']['lng']
            );
        }
        foreach ($response['users'] as $u) {
            $result['users'][] = UserFactory::create(
                (int) $u['user']['pk'],
                $u['user']['username'],
                $u['user']['profile_pic_url'],
                $u['user']['full_name'],
                $u['user']['is_private'],
                $u['user']['is_verified'],
                $u['user']['follower_count']
            );
        }

        return $result;
    }

    /**
     * Return simple result. (Just url, comments, and likes)
     *
     * @return array A simple list of medias
     */
    public function returnSimpleResult(): array
    {
        $list = [];
        foreach($this->result as $key => $result) {
            $list[$key]['id'] = $result->getUser()->getId();
            $list[$key]['username'] = $result->getUser()->getUsername();
            $list[$key]['image_url'] = $result->getUrl();
            $list[$key]['url'] = self::BASE_URI.sprintf(self::MEDIA_ENDPOINT, $result->getCode());
            $list[$key]['comments'] = $result->getCommentsCount();
            $list[$key]['likes'] = $result->getLikesCount();

            // Tags
            $tags = [];
            foreach ($result->getTags() as $tag) {
                array_push($tags, $tag->getName());
            }

            $list[$key]['tags'] = $tags;

            if (get_class($result) === 'Konnco\InstagramCrawler\Model\Video') {
                $list[$key]['type'] = 'video';
                $list[$key]['thumb'] = $result->getThumb();
            } else {
                $list[$key]['type'] = 'photo';
            }
        }

        return $list;
    }

    /**
     * Return full result.
     *
     * @return array A simple list of medias
     */
    public function returnFullResult(): array
    {
        return $this->result;
    }
}
