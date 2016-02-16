<?php

require 'recipe/composer.php';

// defaults
env('cron', false);

set('symlinks',
	[ 
		'web/.htaccess' => '{{ project_root }}/config/.htaccess',
		'config/app-config-local.php' => '{{ project_root }}/config/app-config-local.php',
		'web/UserFiles' => '{{ project_root }}/var/UserFiles',
		'log' => '{{ project_root }}/log',
		'tmp' => '{{ project_root }}/tmp',
	] 
);

// functions

function registerSymlink($from, $to) {
	if (!has('symlinks')) {
        $symlinks = []; // no data
    } else {
        $symlinks = get('symlinks');
    }

    $symlinks[$from] = $to;
    set('symlinks', $symlinks);
}

function injectLikTasks() {
    // вставляем наши таски в таски recipe/composer.php

    //
    // deploy
    //
    before('deploy', 'lik:check-not-committed');
    #task('deploy', [
    #    'deploy:prepare',
    #    'deploy:release',
    #    'deploy:update_code',
    before('deploy:vendors', 'lik:warm-vendor');
    #    'deploy:vendors',
    before('deploy:symlink', 'lik:install-symlinks');
    #    'deploy:symlink',
    #    'cleanup',
    #])->desc('Deploy your project');
    after('deploy', 'lik:clear-apc-cache');
    after('deploy', 'lik:install-cron');
    #
    #after('deploy', 'success');


    //
    // rollback
    //
    before('rollback', 'lik:check-not-committed');
}

function dirExists($dir) {
    $result = (string) run("[ -d '$dir' ] && echo 1 || echo ''");

    return (bool) $result;
}

// tasks

task('lik:clear-apc-cache', function () {
	run('wget --user={{ apc.user }} --password={{ apc.password }} --spider {{ apc_cache_url }}');
})->desc('Clear APC cache');

task('lik:warm-vendor', function() {
    // скопировать vendor с последнего релиза
    run("[ -e {{ deploy_path }}/current/vendor ] && cp -a {{ deploy_path }}/current/vendor {{ deploy_path }}/release || echo 'No previous vendor, nothing to do'");
})->desc('Copy vendor from current release');


task('lik:dirs', function() {
    run("cd {{ project_root }} && mkdir -p tmp log var");
})->desc('Create var log tmp dirs');


task('lik:install-cron', function () {
    if (env('cron')) {
        run("crontab {{ deploy_path }}/cron/crontab");
    }
})->desc('Install cron');

task('lik:install-symlinks', function () {
	if (!has('symlinks')) return null; // no data

	$symlinks = get('symlinks');
	foreach ($symlinks as $from => $to) {
//		writeln("Creating symlink: $from => $to");
		run("ln -s $to {{ deploy_path }}/release/$from");
	}
})->desc('Install symlinks');

task('lik:check-not-committed', function() {
    if (!dirExists("{{ deploy_path }}/current")) {
        return true; // nothing to do
    }

    $repos = (string) run("cd {{ deploy_path }}/current && find ./ -type d -name .git");
    $repos = explode("\n", $repos);
    $changes = array();
    foreach ($repos as $repo) {
        $repo_path = realpath(dirname(env('deploy_path') . '/current/' . $repo));

        $output = run("cd $repo_path && git status --porcelain");
        $output = (string) $output;
        if ($output) {
            $changes[$repo_path] = [ 'type' => 'commit', 'msg' => $output ];
            continue;
        }

        $output = run("cd $repo_path && git log --branches --not --remotes --oneline --simplify-by-decoration --decorate");
        $output = (string) $output;
        if ($output) {
            $changes[$repo_path] = [ 'type' => 'push', 'msg' => $output ];
            continue;
        }
    }

    if ($changes) {
        writeln('<comment>Hey!</comment>');
        foreach ($changes as $repo_path => $change) {
            writeln("\n\n\n");
            write('<comment>');
            writeln(($change['type'] == 'commit') 
                ? 'You have changes in working tree:'
                : 'You have NOT PUSHED COMMITS in repositories:'
            );
            write('</comment>');

            writeln("\n\nRepo: $repo_path\n");
            writeln($change['msg']);

            writeln("\n\n\n");
            write('<comment>');
            writeln(($change['type'] == 'commit') 
                ? 'Commit or revert them.'
                : 'Push them or delete them.'
            );
            write('</comment>');
        }
        throw new Exception('Found not commited/pushed changes.');
    }
})->desc('Check not committed/pushed changes in all git repos');

injectLikTasks();

