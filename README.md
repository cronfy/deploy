# deploy
Local deploy tool

Installation
------------

## If ```composer``` already installed and ```php``` version is 5.6

```sh
composer global config repositories.cronfy/deploy vcs https://github.com/cronfy/deploy
composer global require cronfy/deploy dev-master

# if not yet
echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.bash_profile
```

## No ```composer``` and ```PHP 5.6```

Use kickstart installer.

```sh
wget https://raw.githubusercontent.com/cronfy/deploy/master/doc/kickstart.sh
chmod +x kickstart.sh
./kickstart.sh
rm ./kickstart.sh

# update PATH
. ~/.bash_profile
```

What ```kickstart.sh``` does:

 1. Ensures that ```$HOME/bin/php``` version is 5.6 and ```$HOME/bin``` is in ```$PATH```.
 2. Installs ```composer``` to ```$HOME/bin```.
 3. Installs ```cdep``` and ensures ```$HOME/.composer/vendor/bin``` is in ```$PATH```.

Initialization
--------------

Initializations for specific cases.

## Global initializations (not related to project(s))

```sh
cdep init-composer-token
cdep init-git
cdep init-yii
```

