#!/bin/sh

php -m | grep -qi ioncube && {
	echo "ERROR: ionCube is incompatible with deployer. Please disable ionCube."
	exit 1
}

cd "`dirname $0`/config"

dep deploy "$@"

