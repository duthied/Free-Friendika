#!/usr/bin/env bash

DATABASENAME=${MYSQL_DATABASE:-test}
DATABASEUSER=${MYSQL_USERNAME:-friendica}
DATABASEHOST=${MYSQL_HOST:-localhost}
BASEDIR=$PWD

DBCONFIGS="mysql mariadb"

export MYSQL_DATABASE="$DATABASENAME"
export MYSQL_USERNAME="$DATABASEUSER"
export MYSQL_PASSWORD="friendica"

if [ -z "$PHP_EXE" ]; then
  PHP_EXE=php
fi
PHP=$(which "$PHP_EXE")
# Use the Friendica internal composer
COMPOSER="$BASEDIR/bin/composer.phar"

set -e

_XDEBUG_CONFIG=$XDEBUG_CONFIG
unset XDEBUG_CONFIG

if [ -x "$PHP" ]; then
  echo "Using PHP executable $PHP"
else
  echo "Could not find PHP executable $PHP_EXE" >&2
  exit 3
fi

echo "Installing depdendencies"
$PHP "$COMPOSER" install

PHPUNIT="$BASEDIR/vendor/bin/phpunit"

if [ -x "$PHPUNIT" ]; then
  echo "Using PHPUnit executable $PHPUNIT"
else
  echo "Could not find PHPUnit executable after composer $PHPUNIT" >&2
  exit 3
fi

if ! [ \( -w config -a ! -f config/local.config.php \) -o \( -f config/local.config.php -a -w config/local.config.php \) ]; then
	echo "Please enable write permissions on config and config/config.php" >&2
	exit 1
fi

if [ "$1" ]; then
  FOUND=0
  for DBCONFIG in $DBCONFIGS; do
    if [ "$1" = "$DBCONFIG" ]; then
      FOUND=1
      break
    fi
  done
  if [ $FOUND = 0 ]; then
    echo -e "Unknown database config name \"$1\"\n" >&2
    exit 2
  fi
fi

# Back up existing (dev) config if one exists and backup not already there
if [ -f config/local.config.php ] && [ ! -f config/local.config-autotest-backup.php ]; then
  mv config/local.config.php config/local.config-autotest-backup.php
fi

function cleanup_config {

    if [ -n "$DOCKER_CONTAINER_ID" ]; then
      echo "Kill the docker $DOCKER_CONTAINER_ID"
      docker stop "$DOCKER_CONTAINER_ID"
      docker rm -f "$DOCKER_CONTAINER_ID"
    fi

    cd "$BASEDIR"

    # Restore existing config
    if [ -f config/local.config-autotest-backup.php ]; then
      mv config/local.config-autotest-backup.php config/local.config.php
    fi
}

# restore config on exit
trap cleanup_config EXIT

function execute_tests {
    DB=$1
    echo "Setup environment for $DB testing ..."
    # back to root folder
    cd "$BASEDIR"

    # backup current config
    if [ -f config/local.config.php ]; then
      mv config/local.config.php config/local.config-autotest-backup.php
    fi

    if [ -z "$NOINSTALL" ]; then
      #drop database
      if [ "$DB" == "mysql" ]; then
          if [ -n "$USEDOCKER" ]; then
            echo "Fire up the mysql docker"
            DOCKER_CONTAINER_ID=$(docker run \
                    -e MYSQL_ROOT_PASSWORD=friendica \
                    -e MYSQL_USER="$DATABASEUSER" \
                    -e MYSQL_PASSWORD=friendica \
                    -e MYSQL_DATABASE="$DATABASENAME" \
                    -d mysql)
            DATABASEHOST=$(docker inspect --format="{{.NetworkSettings.IPAddress}}" "$DOCKER_CONTAINER_ID")

          else
            if [ -z "$DRONE" ]; then  # no need to drop the DB when we are on CI
              if [ "mysql" != "$(mysql --version | grep -o mysql)" ]; then
                echo "Your mysql binary is not provided by mysql"
                echo "To use the docker container set the USEDOCKER environment variable"
                exit 3
              fi
              mysql -u "$DATABASEUSER" -pfriendica -e "DROP DATABASE IF EXISTS $DATABASENAME" -h $DATABASEHOST || true
              mysql -u "$DATABASEUSER" -pfriendica -e "CREATE DATABASE $DATABASENAME DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci" -h $DATABASEHOST
            else
              DATABASEHOST=mysql
            fi
          fi

          echo "Waiting for MySQL $DATABASEHOST initialization..."
          if ! bin/wait-for-connection $DATABASEHOST 3306 300; then
            echo "[ERROR] Waited 300 seconds, no response" >&2
            exit 1
          fi

          echo "MySQL is up."
      fi
      if [ "$DB" == "mariadb" ]; then
          if [ -n "$USEDOCKER" ]; then
            echo "Fire up the mariadb docker"
            DOCKER_CONTAINER_ID=$(docker run \
                    -e MYSQL_ROOT_PASSWORD=friendica \
                    -e MYSQL_USER="$DATABASEUSER" \
                    -e MYSQL_PASSWORD=friendica \
                    -e MYSQL_DATABASE="$DATABASENAME" \
                    -d mariadb)
            DATABASEHOST=$(docker inspect --format="{{.NetworkSettings.IPAddress}}" "$DOCKER_CONTAINER_ID")

          else
            if [ -z "$DRONE" ]; then  # no need to drop the DB when we are on CI
              if [ "MariaDB" != "$(mysql --version | grep -o MariaDB)" ]; then
                echo "Your mysql binary is not provided by mysql"
                echo "To use the docker container set the USEDOCKER environment variable"
                exit 3
              fi
              mysql -u "$DATABASEUSER" -pfriendica -e "DROP DATABASE IF EXISTS $DATABASENAME" -h $DATABASEHOST || true
              mysql -u "$DATABASEUSER" -pfriendica -e "CREATE DATABASE $DATABASENAME DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci" -h $DATABASEHOST
            else
              DATABASEHOST=mariadb
            fi
          fi

           echo "Waiting for MariaDB $DATABASEHOST initialization..."
            if ! bin/wait-for-connection $DATABASEHOST 3306 300; then
                echo "[ERROR] Waited 300 seconds, no response" >&2
                exit 1
            fi

            echo "MariaDB is up."
      fi

      if [ -n "$USEDOCKER" ]; then
        echo "Initialize database..."
        docker exec $DOCKER_CONTAINER_ID mysql -u root -pfriendica -e 'CREATE DATABASE IF NOT EXISTS $DATABASENAME;'
      fi

      export MYSQL_HOST="$DATABASEHOST"

      #call installer
      echo "Installing Friendica..."
      "$PHP" ./bin/console.php autoinstall --dbuser="$DATABASEUSER" --dbpass=friendica --dbdata="$DATABASENAME" --dbhost="$DATABASEHOST" --url=https://friendica.local --admin=admin@friendica.local
    fi

    #test execution
    echo "Testing..."
    rm -fr "coverage-html"
    mkdir "coverage-html"
    if [[ "$_XDEBUG_CONFIG" ]]; then
      export XDEBUG_CONFIG=$_XDEBUG_CONFIG
    fi

    COVER=''
    if [ -z "$NOCOVERAGE" ]; then
      COVER="--coverage-clover tests/autotest-clover.xml --coverage-html tests/coverage-html"
    else
      echo "No coverage"
    fi

    # per default, there is no cache installed
    GROUP='--exclude-group REDIS,MEMCACHE,MEMCACHED,APCU'
    if [ "$TEST_SELECTION" == "REDIS" ]; then
      GROUP="--group REDIS"
    fi
    if [ "$TEST_SELECTION" == "MEMCACHE" ]; then
      GROUP="--group MEMCACHE"
    fi
    if [ "$TEST_SELECTION" == "MEMCACHED" ]; then
      GROUP="--group MEMCACHED"
    fi
    if [ "$TEST_SELECTION" == "APCU" ]; then
      GROUP="--group APCU"
    fi
    if [ "$TEST_SELECTION" == "NODB" ]; then
      GROUP="--exclude-group DB,SLOWDB"
    fi

    INPUT="$BASEDIR/tests"
    if [ -n "$2" ]; then
      INPUT="$INPUT/$2"
    fi

    echo "${PHPUNIT[@]}" --configuration tests/phpunit.xml $GROUP $COVER --log-junit "autotest-results.xml" "$INPUT" "$3"
    "${PHPUNIT[@]}" --configuration tests/phpunit.xml $GROUP $COVER --log-junit "autotest-results.xml" "$INPUT" "$3"
    RESULT=$?

    if [ -n "$DOCKER_CONTAINER_ID" ]; then
      echo "Kill the docker $DOCKER_CONTAINER_ID"
      docker stop $DOCKER_CONTAINER_ID
      docker rm -f $DOCKER_CONTAINER_ID
      unset $DOCKER_CONTAINER_ID
    fi
}

#
# Start the test execution
#
if [ -z "$1" ] && [ -n "$TEST_SELECTION" ]; then
  # run all known database configs
  for DBCONFIG in $DBCONFIGS; do
    execute_tests "$DBCONFIG"
  done
else
  FILENAME="$2"
  if [ -n "$2" ] && [ ! -f "tests/$FILENAME" ] && [ "${FILENAME:0:2}" != "--" ]; then
    FILENAME="../$FILENAME"
  fi
  execute_tests "$1" "$FILENAME" "$3"
fi
