-- ==========================================================
-- ESTRUCTURA DE BASE DE DATOS PARA MAX FM (SQLITE)
-- ==========================================================

-- 1. Configuración global: Datos de contacto, SEO y enlaces de apps.
CREATE TABLE IF NOT EXISTS config_general (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre_emisora    TEXT NOT NULL, -- Nombre comercial de la radio
  slogan            TEXT,          -- Frase que identifica la emisora
  logo              TEXT,          -- Ruta de la imagen del logo principal
  favicon           TEXT,          -- Icono para la pestaña del navegador
  descripcion       TEXT,          -- Breve reseña de la emisora
  url_streaming     TEXT,          -- Link directo al flujo de audio
  url_app_android   TEXT,          -- Enlace a la Play Store
  url_app_ios       TEXT,          -- Enlace a la App Store
  telefono          TEXT,
  whatsapp          TEXT,
  email             TEXT,
  direccion         TEXT,
  facebook          TEXT,
  instagram         TEXT,
  tiktok            TEXT,
  youtube           TEXT,
  twitter           TEXT,
  footer_texto      TEXT,          -- Texto informativo al final de la página
  footer_copyright  TEXT,          -- Texto de derechos reservados
  meta_titulo       TEXT,          -- Título optimizado para Google
  meta_descripcion  TEXT,          -- Descripción optimizada para Google
  creado_en         DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Banner Principal: Imágenes rotativas en la página de inicio (Slider).
CREATE TABLE IF NOT EXISTS hero_slides (
  id              INTEGER PRIMARY KEY AUTOINCREMENT,
  imagen          TEXT NOT NULL, -- Ruta de la imagen de fondo
  titulo          TEXT,          -- Título principal sobre el banner
  subtitulo       TEXT,          -- Texto secundario pequeño
  texto           TEXT,          -- Descripción adicional
  mostrar_texto   INTEGER DEFAULT 0,  -- Interruptor para mostrar/ocultar texto (0/1)
  alineacion      TEXT CHECK(alineacion IN ('izquierda','centro','derecha')) DEFAULT 'centro',
  orden           INTEGER DEFAULT 0,         -- Prioridad de aparición (0 es primero)
  activo          INTEGER DEFAULT 1,  -- Estado para pausar el slide sin borrarlo
  creado_en       DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. Locutores: Perfiles del staff y presentadores de la emisora.
CREATE TABLE IF NOT EXISTS locutores (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre            TEXT NOT NULL,
  cargo             TEXT,          -- Ej: Director, DJ, Periodista
  foto              TEXT,          -- Imagen de perfil del locutor
  bio               TEXT,          -- Breve biografía
  instagram_usuario TEXT,          -- @usuario para mostrar en la web
  instagram_url     TEXT,          -- Link directo al perfil
  destacado         INTEGER DEFAULT 0,  -- Si aparece en la sección principal
  orden             INTEGER DEFAULT 0,
  activo            INTEGER DEFAULT 1,
  creado_en         DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 4. Programación: Horarios y días de emisión de los shows.
CREATE TABLE IF NOT EXISTS programas (
  id              INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo          TEXT NOT NULL,
  imagen          TEXT,          -- Poster o arte del programa
  descripcion     TEXT,
  hora_inicio     TEXT,          -- Formato TIME (HH:MM:SS)
  hora_fin        TEXT,
  horario_texto   TEXT,          -- Ej: "Lunes a Viernes"
  dias            TEXT,          -- Array de días en formato JSON (guardado como texto)
  locutor_id      INTEGER,       -- Relación con la tabla locutores
  destacado       INTEGER DEFAULT 0,
  orden           INTEGER DEFAULT 0,
  activo          INTEGER DEFAULT 1,
  creado_en       DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (locutor_id) REFERENCES locutores(id) ON DELETE SET NULL
);

-- 5/6. Tabla única para el Ranking: Aquí el admin mete las canciones directamente.
CREATE TABLE IF NOT EXISTS top5_canciones (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  posicion    INTEGER NOT NULL,              -- Puesto (1, 2, 3, 4, 5)
  titulo      TEXT NOT NULL,     -- Nombre de la canción
  artista     TEXT NOT NULL,     -- Nombre del artista
  portada     TEXT,              -- URL de la imagen
  enlace      TEXT,              -- Link externo
  youtube_url TEXT,              -- Link de YouTube
  fecha_ranking DATE,                    -- Para saber de qué semana es este top
  activo      INTEGER DEFAULT 1,      -- Permite ocultar una canción si es necesario
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 7. Blog/Noticias: Artículos, novedades y actualidad musical.
CREATE TABLE IF NOT EXISTS noticias (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo            TEXT NOT NULL,
  slug              TEXT NOT NULL UNIQUE, -- URL amigable (ej: noticia-musical-2024)
  resumen           TEXT,                  -- Texto corto para la vista previa
  contenido         TEXT,              -- Cuerpo completo de la noticia (HTML)
  imagen_portada    TEXT,          -- Imagen grande interna
  imagen_tarjeta    TEXT,          -- Miniatura para el listado
  categoria         TEXT,          -- Ej: Eventos, Entrevistas, Estrenos
  autor             TEXT,
  destacada         INTEGER DEFAULT 0,  -- Si aparece en la parte superior del blog
  estado            TEXT CHECK(estado IN ('borrador','publicado')) DEFAULT 'borrador',
  fecha_publicacion DATETIME,
  meta_titulo       TEXT,
  meta_descripcion  TEXT,
  creado_en         DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 8. Video Recomendado: Sección destacada para un video único de YouTube.
CREATE TABLE IF NOT EXISTS recomendada (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo_bloque  TEXT,          -- Título de la sección (ej: "Estreno de la semana")
  titulo_video   TEXT,          -- Nombre del video
  descripcion    TEXT,
  youtube_url    TEXT NOT NULL, -- Link para embeber el video
  activo         INTEGER DEFAULT 1,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 9. Chat: Configuración de módulo externo de interacción en vivo.
CREATE TABLE IF NOT EXISTS chat_config (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  titulo         TEXT,          -- Título del chat (ej: "Chat en Vivo")
  subtitulo      TEXT,
  embed_code     TEXT,          -- Código HTML/JS (Iframe) del proveedor de chat
  visible        INTEGER DEFAULT 1,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 10. Usuarios: Gestión de acceso al panel de administración.
CREATE TABLE IF NOT EXISTS usuarios (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  nombre         TEXT NOT NULL,
  email          TEXT NOT NULL UNIQUE,
  password_hash  TEXT NOT NULL, -- Contraseña cifrada
  rol            TEXT CHECK(rol IN ('admin','editor')) DEFAULT 'editor', -- Nivel de permisos
  activo         INTEGER DEFAULT 1,
  ultimo_acceso  DATETIME,
  creado_en      DATETIME DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);