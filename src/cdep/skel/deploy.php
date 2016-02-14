<?php

Deploy\Deploy::init();

require 'recipe/lik-site.php';

// this server
set('repository', '...');

localServer('prod')
#    ->env('branch', 'master')
    ->env('domain', '...')

#    ->env('cron', false)

    ->env('project_root', '/home/.../domains/...')
    ->env('deploy_path', '{{ project_root }}/deploy')

#    ->env('apc_cache_url', 'http://.../.../apc-clear.php')
#    ->env('apc.user', 'cacheuser')
#    ->env('apc.password', 'cachepass')

;


