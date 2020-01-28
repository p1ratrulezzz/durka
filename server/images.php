<?php

use ByJG\Cache\Psr16\ShmopCacheEngine;
use ByJG\Cache\Psr6\CachePool;
use Configuration\Config;
use GuzzleHttp\Client;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

$pool = new CachePool(new ShmopCacheEngine());

$cache_key = Config::getCacheKey('images');
$cache_item = $pool->getItem($cache_key);

if (!$cache_item->isHit()) {
  $client = new Client();
  $options = [];
  $options['query'] = [
    'access_token' => Config::getVkServiceToken(),
    'v' => Config::getVkApiVersion(),
    'filter' => 'owner',
    'count' => 100,
    'offset' => 0,
    'owner_id' => Config::getVkWallId(),
  ];

  $cache_item
    ->set([])
    ->expiresAfter(60);

  $response_object = $client->get('https://api.vk.com/method/wall.get', $options);
  if ($response_object->getStatusCode() == 200) {
    $response = json_decode($response_object->getBody());
    if (!empty($response->response)) {
      $data = [];
      foreach ($response->response->items as $item) {
        if ($item->is_pinned || $item->post_type != 'post' || empty($item->attachments) || !empty($item->text)) {
          continue;
        }

        foreach ($item->attachments as $attachment) {
          if ($attachment->type == 'photo') {
            $data[] = ((array) array_pop($attachment->photo->sizes)) + ['source' => 'vk'];
          }
        }
      }

      $cache_item->set($data);
    }

    $cache_item->expiresAfter(30 * 60); // 30 minutes. @todo: To configs.
  }

  $pool->save($cache_item);
}

$result = [];
$result['images'] = $cache_item->get();

ob_clean();
ob_start();
$output = json_encode($result);
header('Access-Control-Allow-Origin: *');
header('Content-type: text/json; charset=UTF8');
header('Content-size: ' . strlen($output));
header('Cache-control: no-cache; must-revalidate; max-age=0');

echo $output;
ob_get_flush();
