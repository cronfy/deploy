
function plugin() {
	local env="`getEnvPath`"
	if ! [ -e "$env" ] ; then
		echo "Env not found" >&2
		return 1
	fi

	. "$env"

	echo "CREATE DATABASE $DB_NAME;"
	echo "GRANT ALL PRIVILEGES ON $DB_NAME.* to $DB_USER@localhost;"
	echo "SET PASSWORD FOR $DB_USER@localhost = PASSWORD('$DB_PASS');"
}

