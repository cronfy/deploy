<?php

task('clear-apc-cache', function () {
	run('{{ deploy_path }} wget --user={{ apc.user }} --password={{ apc.password }} --spider {{ apc_cache_url }}');
})->desc('Clear APC cache');


