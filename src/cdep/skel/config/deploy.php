<?php

require getenv('HOME') . '/.composer/vendor/cronfy/deploy/recipe/lik-site.php';

set('repository', '~/repo/....');

localServer('prod');
#    ->env('apc_cache_url', 'http://.../.../apc-clear.php')
#    ->env('apc.user', 'cacheuser')
#    ->env('apc.password', 'cachepass')
;

# symlink example
# registerSymlink('web/.htaccess', '{{ project_root }}/config/.htaccess.dev');

