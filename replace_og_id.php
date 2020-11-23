<?php

$filepath = __DIR__ . '/index.html';
$end_signature = '<!--OG_BLOCK_END-->';

/****************************************************************/
$content = file_get_contents($filepath);

$block_start_pos = mb_strpos($content, '<!--OG_BLOCK_BEGIN-->');
$block_end_pos = mb_strpos($content, $end_signature);

if ($block_start_pos === false || $block_end_pos === false) {
  die('Can\'t find the OG block');
}

$block_end_pos += mb_strlen($end_signature);
$og_block_content = mb_substr($content, $block_start_pos, $block_end_pos - $block_start_pos);

$new_id = hash('adler32', time() . microtime());
$new_og_block = preg_replace('/(\&og_unique_id\=)([^\"\'])+/im', '${1}' . $new_id, $og_block_content);

if ($new_og_block === $og_block_content) {
  die('No change in og block');
}

$new_content = mb_substr($content, 0, $block_start_pos) . $new_og_block . mb_substr($content, $block_end_pos);

file_put_contents($filepath, $new_content);
