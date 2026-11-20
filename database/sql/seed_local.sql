-- Datos mínimos para desarrollo local (docker compose). Nunca en producción.
INSERT INTO gestopago.api_keys (api_key, description)
VALUES ('local-dev-key', 'Cliente local / Bruno')
ON CONFLICT (api_key) DO NOTHING;

INSERT INTO gestopago.categories (id, name) VALUES
    (1, 'Recargas'),
    (2, 'Servicios'),
    (3, 'Tarjetas de regalo')
ON CONFLICT (id) DO NOTHING;
