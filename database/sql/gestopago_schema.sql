-- =============================================================================
-- Esquema "gestopago" para DESARROLLO LOCAL y CI.
--
-- wsgestopago dependía de funciones de PostgreSQL cuyo DDL no está versionado.
-- Este archivo las RECONSTRUYE a partir de cómo se invocan (nombre, número y
-- orden de argumentos, valor devuelto). Las columnas de las tablas son una
-- aproximación razonable, NO el esquema productivo.
--
-- En ambientes reales usa el esquema existente; para alinearlo, reemplaza este
-- archivo con un `pg_dump --schema-only --schema=gestopago` de producción.
--
-- Nombres con erratas (insert_trasaction, get_token_gestopago_avaible,
-- updated_*) se conservan a propósito: son los de la base existente.
-- =============================================================================

CREATE SCHEMA IF NOT EXISTS gestopago;

-- --- Tablas ------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS gestopago.api_keys (
    id          bigserial PRIMARY KEY,
    api_key     text        NOT NULL UNIQUE,
    description text,
    active      boolean     NOT NULL DEFAULT true,
    created_at  timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS gestopago.provider_tokens (
    id         bigserial PRIMARY KEY,
    token      text        NOT NULL,
    created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS gestopago.categories (
    id     integer PRIMARY KEY,
    name   text    NOT NULL,
    active boolean NOT NULL DEFAULT true
);

CREATE TABLE IF NOT EXISTS gestopago.products (
    id_producto            integer PRIMARY KEY,
    servicio               text    NOT NULL,
    producto               text    NOT NULL,
    id_servicio            integer NOT NULL,
    id_cat_tipo_servicio   integer NOT NULL,
    tipo_front             integer NOT NULL,
    has_digito_verificador text,
    precio                 text,
    show_ayuda             text,
    tipo_referencia        text,
    updated_at             timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS gestopago.commission_business (
    id          bigserial PRIMARY KEY,
    business_id integer     NOT NULL,
    commission  text        NOT NULL,
    created_at  timestamptz NOT NULL DEFAULT now(),
    updated_at  timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS gestopago.transactions (
    id            bigserial PRIMARY KEY,
    telefono      text,
    id_servicio   integer,
    id_producto   integer,
    hora_local    text,
    referencia    text,
    monto_pago    text,
    upc           text        NOT NULL UNIQUE,
    request_in    text        NOT NULL,
    business_id   integer     NOT NULL,
    id_tx         text,
    response_json text,
    response_xml  text,
    created_at    timestamptz NOT NULL DEFAULT now(),
    updated_at    timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS transactions_business_created_idx
    ON gestopago.transactions (business_id, created_at);

-- --- API keys de clientes ----------------------------------------------------

CREATE OR REPLACE FUNCTION gestopago.validate_token(p_token text)
RETURNS boolean LANGUAGE sql STABLE AS $$
    SELECT EXISTS (SELECT 1 FROM gestopago.api_keys WHERE api_key = p_token AND active);
$$;

-- --- JWT del proveedor (vigencia 24 h) ------------------------------------------

CREATE OR REPLACE FUNCTION gestopago.get_token_gestopago_avaible()
RETURNS text LANGUAGE sql STABLE AS $$
    SELECT COALESCE(
        (SELECT token FROM gestopago.provider_tokens
          WHERE created_at > now() - interval '24 hours'
          ORDER BY created_at DESC LIMIT 1),
        'FAIL'
    );
$$;

CREATE OR REPLACE FUNCTION gestopago.insert_token_gestopago(p_token text)
RETURNS boolean LANGUAGE sql AS $$
    INSERT INTO gestopago.provider_tokens (token) VALUES (p_token);
    SELECT true;
$$;

-- --- Catálogo -----------------------------------------------------------------

CREATE OR REPLACE FUNCTION gestopago.get_categories()
RETURNS SETOF gestopago.categories LANGUAGE sql STABLE AS $$
    SELECT * FROM gestopago.categories WHERE active ORDER BY name;
$$;

CREATE OR REPLACE FUNCTION gestopago.get_products_by_category(p_category_id integer)
RETURNS SETOF gestopago.products LANGUAGE sql STABLE AS $$
    SELECT * FROM gestopago.products WHERE id_cat_tipo_servicio = p_category_id ORDER BY servicio, producto;
$$;

CREATE OR REPLACE FUNCTION gestopago.insert_product(
    p_servicio text, p_producto text, p_id_servicio integer, p_id_producto integer,
    p_id_cat_tipo_servicio integer, p_tipo_front integer, p_has_digito_verificador text,
    p_precio text, p_show_ayuda text, p_tipo_referencia text
) RETURNS boolean LANGUAGE sql AS $$
    INSERT INTO gestopago.products AS p (
        id_producto, servicio, producto, id_servicio, id_cat_tipo_servicio, tipo_front,
        has_digito_verificador, precio, show_ayuda, tipo_referencia
    ) VALUES (
        p_id_producto, p_servicio, p_producto, p_id_servicio, p_id_cat_tipo_servicio, p_tipo_front,
        p_has_digito_verificador, p_precio, p_show_ayuda, p_tipo_referencia
    )
    ON CONFLICT (id_producto) DO UPDATE SET
        servicio = EXCLUDED.servicio,
        producto = EXCLUDED.producto,
        id_servicio = EXCLUDED.id_servicio,
        id_cat_tipo_servicio = EXCLUDED.id_cat_tipo_servicio,
        tipo_front = EXCLUDED.tipo_front,
        has_digito_verificador = EXCLUDED.has_digito_verificador,
        precio = EXCLUDED.precio,
        show_ayuda = EXCLUDED.show_ayuda,
        tipo_referencia = EXCLUDED.tipo_referencia,
        updated_at = now();
    SELECT true;
$$;

-- --- Comisiones -----------------------------------------------------------------

CREATE OR REPLACE FUNCTION gestopago.get_commission_by_business(p_business_id integer)
RETURNS SETOF gestopago.commission_business LANGUAGE sql STABLE AS $$
    SELECT * FROM gestopago.commission_business WHERE business_id = p_business_id ORDER BY id;
$$;

CREATE OR REPLACE FUNCTION gestopago.insert_commission_business(p_business_id integer, p_commission text)
RETURNS SETOF gestopago.commission_business LANGUAGE sql AS $$
    INSERT INTO gestopago.commission_business (business_id, commission)
    VALUES (p_business_id, p_commission)
    RETURNING *;
$$;

CREATE OR REPLACE FUNCTION gestopago.updated_commission_business(p_commission_business_id integer, p_commission text)
RETURNS SETOF gestopago.commission_business LANGUAGE sql AS $$
    UPDATE gestopago.commission_business
       SET commission = p_commission, updated_at = now()
     WHERE id = p_commission_business_id
    RETURNING *;
$$;

-- --- Transacciones ------------------------------------------------------------

CREATE OR REPLACE FUNCTION gestopago.insert_trasaction(
    p_telefono text, p_id_servicio integer, p_id_producto integer, p_hora_local text,
    p_referencia text, p_monto_pago text, p_upc text, p_request_in text, p_business_id integer
) RETURNS bigint LANGUAGE sql AS $$
    INSERT INTO gestopago.transactions (
        telefono, id_servicio, id_producto, hora_local, referencia, monto_pago, upc, request_in, business_id
    ) VALUES (
        p_telefono, p_id_servicio, p_id_producto, p_hora_local, p_referencia, p_monto_pago, p_upc, p_request_in, p_business_id
    )
    RETURNING id;
$$;

CREATE OR REPLACE FUNCTION gestopago.updated_transaction(
    p_response_json text, p_id_tx text, p_transaction_id bigint, p_response_xml text
) RETURNS boolean LANGUAGE sql AS $$
    UPDATE gestopago.transactions
       SET response_json = p_response_json, id_tx = p_id_tx, response_xml = p_response_xml, updated_at = now()
     WHERE id = p_transaction_id;
    SELECT true;
$$;

CREATE OR REPLACE FUNCTION gestopago.get_request_transaction_by_upc(p_upc text)
RETURNS text LANGUAGE sql STABLE AS $$
    SELECT request_in FROM gestopago.transactions WHERE upc = p_upc;
$$;

CREATE OR REPLACE FUNCTION gestopago.get_response_transaction_by_upc(p_upc text)
RETURNS text LANGUAGE sql STABLE AS $$
    SELECT response_json FROM gestopago.transactions WHERE upc = p_upc;
$$;

CREATE OR REPLACE FUNCTION gestopago.get_transaction_by_business(p_business_id text, p_from text, p_to text)
RETURNS TABLE (
    id bigint, upc text, id_tx text, telefono text, id_servicio integer, id_producto integer,
    referencia text, monto_pago text, created_at timestamptz
) LANGUAGE sql STABLE AS $$
    SELECT t.id, t.upc, t.id_tx, t.telefono, t.id_servicio, t.id_producto, t.referencia, t.monto_pago, t.created_at
      FROM gestopago.transactions t
     WHERE t.business_id = p_business_id::integer
       AND t.created_at >= p_from::date
       AND t.created_at <  p_to::date + 1
     ORDER BY t.created_at DESC;
$$;
