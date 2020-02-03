<?php

use ByJG\Cache\Psr16\ShmopCacheEngine;
use ByJG\Cache\Psr6\CachePool;
use Configuration\Config;
use GuzzleHttp\Client;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';
ob_start();

define('PERPAGE_COUNT', 100);

function getFromVk($offset, $count, &$images) {
  $client = new Client();
  $options = [];
  $options['query'] = [
    'access_token' => Config::getVkServiceToken(),
    'v' => Config::getVkApiVersion(),
    'filter' => 'owner',
    'count' => $count,
    'offset' => $offset,
    'owner_id' => Config::getVkWallId(),
  ];

  $response_object = $client->get('https://api.vk.com/method/wall.get', $options);
  if ($response_object->getStatusCode() == 200) {
    $response = json_decode($response_object->getBody());
    if (!empty($response->response)) {
      $data = [];
      $data['count_all'] = $response->response->count;
      $data['offset'] = $offset;
      foreach ($response->response->items as $item) {
        if (!empty($item->is_pinned) || $item->post_type != 'post' || empty($item->attachments) || !empty($item->text)) {
          continue;
        }

        foreach ($item->attachments as $attachment) {
          if ($attachment->type == 'photo') {
            $id = hash('adler32', $item->id . $attachment->photo->id);
            $meta = [
              'source' => 'vk',
              'id' => $id,
            ];

            if (isset($images[$id])) {
              continue;
            }

            $image = ((array) array_pop($attachment->photo->sizes)) + $meta;

            // Filter images that are not memes.
            if ($image['height'] < 100 || $image['width'] < 100) {
              continue;
            }

            $images[$id] = $image;
          }
        }
      }

      return $data;
    }
  }

  return FALSE;
}

$pool = new CachePool(new ShmopCacheEngine());

$cache_key = Config::getCacheKey('images');

$cache_item = $pool->getItem($cache_key);
$cache_item->expiresAt(NULL);

$cache_meta_item = $pool->getItem($cache_key . '_meta');
$cache_meta_item->expiresAfter(30 * 60); // 30 Minutes

if (!$cache_meta_item->isHit() || !empty($_GET['cc'])) {
  $cache_meta_item->set([
    'count_all' => 0,
    'offset' => 0,
  ]);
}

$cache_meta = $cache_meta_item->get();
if (!$cache_item->isHit() || !empty($_GET['cc'])) {
  $cache_item->set([
    'images' => [],
  ]);
}

$cache = $cache_item->get();

// Check if there are possibly some new data.
if (($cache_meta['count_all'] == 0 || $cache_meta['offset'] < $cache_meta['count_all']) && ($cache_new = getFromVk($cache_meta['offset'], PERPAGE_COUNT, $cache['images']))) {
  $cache_meta['offset'] += PERPAGE_COUNT;

  if ($cache_meta['count_all'] != $cache_new['count_all'] || !empty($cache_meta['updating'])) {
    $cache_meta['count_all'] = $cache_new['count_all'];
    $cache_meta['updating'] = $cache_meta['offset'] < $cache_meta['count_all'];

    $cache_item->set($cache);
    $cache_meta_item->set($cache_meta);

    $pool->saveDeferred($cache_item);
    $pool->saveDeferred($cache_meta_item);
  }

}

$pool->commit();

$result = [];
$result['images'] = $cache['images'];

ob_clean();
ob_start();
$output = json_encode($result);
header('Access-Control-Allow-Origin: *');
header('Content-type: text/json; charset=UTF8');
header('Content-size: ' . strlen($output));
header('Cache-control: no-cache; must-revalidate; max-age=0');

echo $output;
ob_get_flush();
