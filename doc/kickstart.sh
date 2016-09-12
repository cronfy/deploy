#!/bin/sh

check_php_version() {
        local php="$1" ok

        ok=`$php -r 'if (PHP_VERSION_ID > 50600 && PHP_VERSION_ID < 50700) echo "php_version_ok";'`

        if [ "php_version_ok" != "$ok" ] ; then
                return 1
        fi

        return 0
}

check_composer_version() {
        # global php
        local composer="$1"

        if $php $composer --version | head -n 1 | grep '^Composer version 1.2\.' ; then
                return 0
        fi

        return 1
}

check_cdep_version() {
        local cdep="$1"

        if $cdep --version | head -n 1 | grep '^Cdep local deploy tool v0.0.1$' ; then
                return 0
        fi

        return 1
}

cd


mkdir -p bin
if [ -e bin/php ] ; then
        if ! check_php_version bin/php ; then
                bin/php -v
                echo
                echo "ERROR: Not supported php version installed in ~/bin"
                exit 1
        fi

        echo "PHP already instaled..."
else
        php=`which php56`
        if [ -z "$php" ] ; then
                echo "ERROR: PHP 5.6 not found on server."
                exit 1
        fi
        if [ ! `check_php_version "$php"` ] ; then
                $php -v
                echo
                echo "ERROR: Not supported php 5.6 version installed on server at $php"
                exit 1
        fi

        echo "Installing PHP in ~/bin..."
        ln -s "$php" bin/php
fi

if [ "`which php`" != "$HOME/bin/php" ] ; then
    echo "Updating PATH environment variable..."
    echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bash_profile
else
    echo "PATH environment variable is ok..."
fi

php=$HOME/bin/php

if [ -e bin/composer ] ; then
        if ! check_composer_version bin/composer ; then
                bin/composer --version
                echo
                echo "ERROR: Not supported composer version installed in ~/bin"
                exit 1
        fi

        echo "Composer already instaled..."
else
    echo "Installing composer..."

    rm -f composer-setup.php
    $php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    $php -r "if (hash_file('SHA384', 'composer-setup.php') === 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    $php composer-setup.php
    $php -r "unlink('composer-setup.php');"
    mv composer.phar bin/composer
    chmod +x bin/composer

    if ! check_composer_version bin/composer ; then
            bin/composer --version
            echo
            echo "ERROR: Failed to istall proper composer version to ~/bin"
            exit 1
    fi
fi

composer=$HOME/bin/composer
cdep=$HOME/.composer/vendor/bin/cdep

if [ -e "$cdep" ] ; then
    if ! check_cdep_version $cdep ; then
        $cdep --version
        echo
        echo "ERROR: Not supported cdep version installed in $cdep"
        exit 1
    fi

    echo "Cdep already instaled"
else
    echo "Installing cdep..."

    $php $composer global config repositories.cronfy/deploy vcs https://github.com/cronfy/deploy
    $php $composer global require cronfy/deploy dev-master

    if ! check_cdep_version $cdep ; then
        $cdep --version
        echo
        echo "ERROR: Failed to install proper version of cdep to $cdep"
        exit 1
    fi
fi

if [ "`which cdep`" != "$cdep" ] ; then
    echo "Updating PATH environment variable for composer/vendor/bin..."
    echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bash_profile
else
    echo "PATH environment variable for composer/vendor/bin is ok..."
fi



