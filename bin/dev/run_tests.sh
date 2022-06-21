#!/bin/sh

PHPUNIT_BIN="./vendor/bin/phpunit"

if [ -z "${FRIENDICA_MYSQL_HOST}" -o -z "${FRIENDICA_MYSQL_DATABASE}" -o -z "${FRIENDICA_MYSQL_USER}" ]
then
	echo "$0: Please add this to your ~/.bashrc file:

export FRIENDICA_MYSQL_HOST=\"localhost\"
export FRIENDICA_MYSQL_DATABASE=\"friendica_test\"
export FRIENDICA_MYSQL_USER=\"friendica_test\"
export FRIENDICA_MYSQL_PASSWORD=\"friendica_test\"
# Optional:
#export FRIENDICA_MYSQL_PORT=\"\"
#export FRIENDICA_MYSQL_SOCKET=\"\"

And create the user/password and database (schema). This script will map then all variables for you and call phpunit properly."
	exit 255
elif [ ! -e "${PHPUNIT_BIN}" ]
then
	echo "$0: Cannot find '${PHPUNIT_BIN}' executable."
	exit 255
fi

export MYSQL_HOST="${FRIENDICA_MYSQL_HOST}"
export MYSQL_DATABASE="${FRIENDICA_MYSQL_DATABASE}"
export MYSQL_USER="${FRIENDICA_MYSQL_USER}"
export MYSQL_PASSWORD="${FRIENDICA_MYSQL_PASSWORD}"
export MYSQL_PORT="${FRIENDICA_MYSQL_PORT}"
export MYSQL_SOCKET="${FRIENDICA_MYSQL_SOCKET}"

echo "$0: Running unit tests ..."
${PHPUNIT_BIN} -v tests/ > /tmp/friendica-phpunit.log 2>/dev/null
STATUS=$?
echo "$0: Returned status: ${STATUS}"
exit ${STATUS}
