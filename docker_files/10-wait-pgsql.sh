#!/bin/sh

echo "* Waiting for PostgreSQL at $DB_HOST:$DB_PORT" 1>&2
while ! nc -v -z -w3 $DB_HOST $DB_PORT >/dev/null 2>&1; do
        sleep 1
done
echo "* PostgreSQL is up" 1>&2
