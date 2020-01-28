<?php

use Configuration\Config;

Config::set('vk_service_token', "YOUR TOKEN HERE");
Config::set('vk_api_version', "5.103");
Config::set('vk_wall_id', "ID OF THE WALL TO FETCH DATA FROM");

// @todo: More caching options.
// @todo: Doesn't work now.
Config::set('cache_type', "shmop");
// Config::set('cache_host', "localhost");
// Config::set('cache_port', "27017");
// Config::set('cache_auth', "localhost");