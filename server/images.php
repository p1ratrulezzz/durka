<?php

use ByJG\Cache\Psr16\ShmopCacheEngine;
use ByJG\Cache\Psr6\CachePool;
use Configuration\Config;
use GuzzleHttp\Client;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';
ob_start();

define('PERPAGE_COUNT', 100);

function getFromVk($offset, $count, &$images, $wall_id) {
  $client = new Client();
  $options = [];
  $options['query'] = [
    'access_token' => Config::getVkServiceToken(),
    'v' => Config::getVkApiVersion(),
    'filter' => 'owner',
    'count' => $count,
    'offset' => $offset,
    'owner_id' => $wall_id,
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

$wall_id = !empty($_GET['wall_id']) && is_numeric($_GET['wall_id']) ? intval($_GET['wall_id']) : Config::getVkWallId();

$pool = new CachePool(new ShmopCacheEngine());

$cache_key = Config::getCacheKey('images:' . $wall_id);

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
if (($cache_meta['count_all'] == 0 || $cache_meta['offset'] < $cache_meta['count_all']) && ($cache_new = getFromVk($cache_meta['offset'], PERPAGE_COUNT, $cache['images'], $wall_id))) {
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

$action = $_GET['action'] ?? 'getImages';

$result = [];
$output = '';

ob_clean();
ob_start();

switch ($action) {
  case 'randomImage':
    $session_id = $_GET['session_id'] ?? null;

    $image = null;
    if ($session_id) {
      $session_cache = $pool->getItem($cache_key. '_' . $session_id);

      $session_cache_data = $session_cache->get();
      if (empty($session_cache_data)) {
        $session_cache_data = $cache['images'];
      }

      $key = array_rand($session_cache_data);
      $image = $session_cache_data[$key];
      unset($session_cache_data[$key]);

      $session_cache->set($session_cache_data);
      $session_cache->expiresAfter(10 * 60); // 10 minutes.

      $pool->saveDeferred($session_cache);
    }
    else {
      $key = array_rand($cache['images']);
      $image = $cache['images'][$key];
    }


    if (!empty($image['url'])) {
      header('Content-type: image/jpeg');
      $output = file_get_contents($image['url']);
    }
    elseif (!empty($image['data'])) {
      header('Content-type: image/jpeg');
      $output = base64_decode($image['data']);
    }

    header('Vary: ETag');
    header('ETag: W/"' . hash('adler32', serialize($image)) . '"');
    break;

  default:
    $result['images'] = array_values($cache['images']);

    header('Content-type: text/json; charset=UTF8');
    $output = json_encode($result);
    break;
}

$pool->commit();

header('Access-Control-Allow-Origin: *');
header('Content-size: ' . strlen($output));
header('Cache-control: public; max-age=60');

echo $output;
ob_get_flush();
