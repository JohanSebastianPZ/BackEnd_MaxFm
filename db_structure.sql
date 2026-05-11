-- =============================================================
-- ESQUEMA REAL DE LA BASE DE DATOS — MAX FM (SQLite)
-- Generado desde database.db (fuente de verdad)
-- =============================================================

-- 1. Configuración general de la emisora
CREATE TABLE IF NOT EXISTS config_general (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre_emisora   TEXT    NOT NULL,
  slogan           TEXT,
  logo             TEXT,
  favicon          TEXT,
  descripcion      TEXT,
  url_streaming    TEXT,
  url_app_android  TEXT,
  url_app_ios      TEXT,
  telefono         TEXT,
  whatsapp         TEXT,
  email            TEXT,
  direccion        TEXT,
  facebook         TEXT,
  instagram        TEXT,
  tiktok           TEXT,
  youtube          TEXT,
  twitter          TEXT,
  footer_texto     TEXT,
  footer_copyright TEXT,
  meta_titulo      TEXT,
  meta_descripcion TEXT,
  hero_velocidad   INTEGER  DEFAULT 8500,
  creado_en        DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Banners / Slider principal (Hero)
CREATE TABLE IF NOT EXISTS hero_slides (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  imagen        TEXT    NOT NULL,
  imagen_movil  TEXT,                                          -- imagen 9:16 para móvil (opcional)
  titulo        TEXT,
  subtitulo     TEXT,
  texto         TEXT,
  mostrar_texto INTEGER  DEFAULT 0,
  alineacion    TEXT     DEFAULT 'centro',
  orden         INTEGER  DEFAULT 0,
  activo        INTEGER  DEFAULT 1,
  creado_en     DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Locutores / Staff
CREATE TABLE IF NOT EXISTS locutores (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre            TEXT    NOT NULL,
  cargo             TEXT,
  foto              TEXT,
  bio               TEXT,
  instagram_usuario TEXT,
  instagram_url     TEXT,
  destacado         INTEGER  DEFAULT 0,
  orden             INTEGER  DEFAULT 0,
  activo            INTEGER  DEFAULT 1,
  creado_en         DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Programas / Parrilla de emisión
CREATE TABLE IF NOT EXISTS programas (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo         TEXT    NOT NULL,
  imagen         TEXT,
  descripcion    TEXT,
  hora_inicio    TEXT,
  hora_fin       TEXT,
  horario_texto  TEXT,
  dias           TEXT,                                         -- JSON array de días (ej: ["lunes","martes"])
  locutor_id     INTEGER,
  destacado      INTEGER  DEFAULT 0,
  orden          INTEGER  DEFAULT 0,
  activo         INTEGER  DEFAULT 1,
  creado_en      DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (locutor_id) REFERENCES locutores(id) ON DELETE SET NULL
);

-- 5. Top 5 Canciones
CREATE TABLE IF NOT EXISTS top5_canciones (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  posicion       INTEGER  NOT NULL,
  titulo         TEXT     NOT NULL,
  artista        TEXT     NOT NULL,
  youtube_url    TEXT,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 6. Noticias / Blog
CREATE TABLE IF NOT EXISTS noticias (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo         TEXT    NOT NULL,
  slug           TEXT    NOT NULL UNIQUE,
  resumen        TEXT,
  contenido      TEXT,
  imagen         TEXT,
  creado_en      DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 7. Video Recomendado
CREATE TABLE IF NOT EXISTS recomendada (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo_bloque  TEXT,
  titulo_video   TEXT,
  descripcion    TEXT,
  youtube_url    TEXT    NOT NULL,
  activo         INTEGER  DEFAULT 1,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 8. Eventos
CREATE TABLE IF NOT EXISTS eventos (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  imagen         TEXT    NOT NULL,
  titulo         TEXT,
  orden          INTEGER  DEFAULT 0,
  activo         INTEGER  DEFAULT 1,
  creado_en      DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 9. Configuración del Chat
CREATE TABLE IF NOT EXISTS chat_config (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo         TEXT,
  subtitulo      TEXT,
  embed_code     TEXT,
  visible        INTEGER  DEFAULT 1,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 10. Usuarios del panel de administración
CREATE TABLE IF NOT EXISTS usuarios (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre         TEXT    NOT NULL,
  email          TEXT    NOT NULL UNIQUE,
  password_hash  TEXT    NOT NULL,
  rol            TEXT     DEFAULT 'editor',                    -- 'admin' | 'editor'
  token_sesion   TEXT,
  last_login_ip  TEXT,
  activo         INTEGER  DEFAULT 1,
  ultimo_acceso  DATETIME,
  creado_en      DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 11. Visitas al sitio público (contador de audiencia)
CREATE TABLE IF NOT EXISTS visitas (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  ip         TEXT    NOT NULL,
  fecha      DATE    NOT NULL,
  creado_en  DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_visitas_fecha ON visitas(fecha);
CREATE INDEX IF NOT EXISTS idx_visitas_ip_fecha ON visitas(ip, fecha);
