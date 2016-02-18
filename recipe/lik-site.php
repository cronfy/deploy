<?php

require 'recipe/composer.php';

// defaults
env('cron', false);
env('branch', 'master');
env('apc_cache_url', false);
env('project_root', dirname(dirname($deployFile))); // deploy.php по умолчанию в project_root/config
env('deploy_path', '{{ project_root }}/deploy');

set('symlinks',
	[ 
		'web/.htaccess' => '.htaccess.prod',
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

function hasUncommitted($dir) {
        $changes = array();

        $output = run("cd $dir && git status --porcelain");
        $output = (string) $output;

        if ($output) {
            $changes = [ 'type' => 'commit', 'msg' => $output ];
        }

        return $changes;
}

// tasks

task('lik:clear-apc-cache', function () {
    if (!env('apc_cache_url')) return null;
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
        // выполнется после успешного деплоя, поэтому смотрим в current/
        run("crontab {{ deploy_path }}/current/cron/crontab");
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

        if ($uncommitted = hasUncommitted($repo_path)) {
            $changes[$repo_path] = $uncommitted;
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

task('lik:commit', function() {
    if (!hasUncommitted('{{ deploy_path }}/current')) {
        throw new Exception('Изменения не найдены');
    }

    cd('{{ deploy_path }}/current');
    writeln("\n\n\n");
    writeln('<info>Изменения в репозитории:</info>');
    writeln("\n\n\n<info>*** git status\n==================================</info>");
    writeln(run("git status"));
    writeln("\n\n\n<info>*** git diff\n==================================</info>");
    writeln(run("git --no-pager diff"));
    writeln("\n\n\n<comment>Добавляем все изменения (измененные и новые файлы) в репозиторий.\nВ коммит попадут следующие файлы:</comment>\n");
    writeln(run("git status --porcelain"));
    writeln("\n\n<comment>Введите описание коммита в ОДНУ строку (Ctrl-C - отмена, Enter - завершить сообщение).</comment>");
    $desc = ask("Описание коммита");

    if (!$desc) {
        throw new Exception('Не введено описание коммита.');
    }

    writeln("\n\n\n<info>*** Коммит:\n=======================================================\n");
    writeln("Файлы:</info>\n");
    writeln(run("git status --porcelain"));
    writeln("\n<info>Описание:</info> $desc");

    if (!($ok = askConfirmation("Отправить коммит?"))) {
        throw new Exception('Коммит не подтвержден.');
    }

    run("git commit -a -m " . escapeshellarg($desc));
//    run("git commit push");

    writeln("\n<info>Коммит отправлен в репозиторий.</info>");
})->desc('Commit changes in working dir');

injectLikTasks();

