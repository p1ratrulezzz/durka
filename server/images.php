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
            $meta = [
              'source' => 'vk',
              'id' => hash('adler32', $item->id . $attachment->photo->id),
            ];

            $image = ((array) array_pop($attachment->photo->sizes)) + $meta;
            if ($image['height'] < 50) {
              continue;
            }

            $images[] = $image;
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

if (!$cache_item->isHit() || !empty($_GET['cc'])) {
  $cache = [
    'count_all' => 0,
    'offset' => 0,
    'images' => [],
  ];
}
else {
  $cache = $cache_item->get();
}

// Check if there are possibly some new data.
if (($cache['count_all'] == 0 || $cache['offset'] < $cache['count_all']) && ($cache_new = getFromVk($cache['offset'], PERPAGE_COUNT, $cache['images']))) {
  $cache['offset'] += PERPAGE_COUNT;
  $cache_item->set($cache);
  $pool->save($cache_item);
}

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
