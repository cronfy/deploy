<?php

require 'recipe/composer.php';

// defaults
env('cron', false);

// tasks

task('lik:clear-apc-cache', function () {
	run('wget --user={{ apc.user }} --password={{ apc.password }} --spider {{ apc_cache_url }}');
})->desc('Clear APC cache');

task('lik:warm-vendor', function() {
    // скопировать vendor с последнего релиза
    run("[ -e {{ deploy_path }}/current/vendor ] && cp -a {{ deploy_path }}/current/vendor {{ deploy_path }}/release");
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


