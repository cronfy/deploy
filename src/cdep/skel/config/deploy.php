<?php

Deploy\Deploy::init();

require 'recipe/lik-site.php';

// this server
set('repository', '~/repo/....');

localServer('prod');
#    ->env('apc_cache_url', 'http://.../.../apc-clear.php')
#    ->env('apc.user', 'cacheuser')
#    ->env('apc.password', 'cachepass')
;


