#!/bin/sh
# Inicializa la base local (y la de pruebas) con el esquema de referencia de gestopago.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<SQL
CREATE DATABASE ${POSTGRES_DB}_test OWNER ${POSTGRES_USER};
SQL

for db in "$POSTGRES_DB" "${POSTGRES_DB}_test"; do
    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$db" -f /sql/gestopago_schema.sql
done

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" -f /sql/seed_local.sql
