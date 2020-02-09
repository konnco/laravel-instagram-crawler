# Laravel Instagram Crawler

This package is designed to do simple Instagram Crawler in Laravel framework.

This package based on this repository [Smochin/instagram-php-crawler](https://github.com/smochin/instagram-php-crawler).
Because this package has not been updated for more than 2 years, we make a copy and do some changes about updated instagram's data.

***This package is still in alpha version, so the update may broke your application.***

## Installation
Package is available on [Packagist](https://packagist.org/packages/konnco/laravel-instagram-crawler),
you can install it using [Composer](http://getcomposer.org).

```shell
composer require konnco/laravel-instagram-crawler
```

### Dependencies
- PHP 7
- json extension
- cURL extension

## Get started

### Initialize the Crawler
```php
$crawler = new Konnco\InstagramCrawler\InstagramCrawler();
```

### Get a list of recently tagged media
```php
$media = $crawler->getMediaByTag('php');
```

### Get a list of recent media from a given location
```php
$media = $crawler->getMediaByLocation(225963881);
```

### Get the most recent media published by a user
```php
$media = $crawler->getMediaByUser('instagram');
```

### Get information about a media
```php
$media = $crawler->getMedia('0sR6OhmwCQ');
```

### Get information about a user
```php
$user = $crawler->getUser('jamersonweb');
```

### Get information about a location
```php
$location = $crawler->getLocation(225963881);
```

### Get information about a tag
```php
$tag = $crawler->getTag('php');
```

### Search for hashtags, locations and users
```php
$result = $crawler->search('recife');
```

### Return the simple result from instagram (url, image url, comment count, and like count)
```php
$media = $crawler->getMediaByUser('instagram')->returnSimpleResult();
```

### Return the full result from instagram
```php
$media = $crawler->getMediaByUser('instagram')->returnFullResult();
```

## Authors
[//]: contributor-faces
<a href="https://github.com/ijalnasution">@ijalnasution</a>
## Contributing
we appreciate all contributions, feel free to write some code or request package.
