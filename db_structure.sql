-- ==========================================================
-- ESTRUCTURA DE BASE DE DATOS PARA MAX FM
-- ==========================================================

-- 1. Configuración global: Datos de contacto, SEO y enlaces de apps.
CREATE TABLE config_general (
  id                INT PRIMARY KEY AUTO_INCREMENT,
  nombre_emisora    VARCHAR(100) NOT NULL, -- Nombre comercial de la radio
  slogan            VARCHAR(200),          -- Frase que identifica la emisora
  logo              VARCHAR(255),          -- Ruta de la imagen del logo principal
  favicon           VARCHAR(255),          -- Icono para la pestaña del navegador
  descripcion       TEXT,                  -- Breve reseña de la emisora
  url_streaming     VARCHAR(255),          -- Link directo al flujo de audio
  url_app_android   VARCHAR(255),          -- Enlace a la Play Store
  url_app_ios       VARCHAR(255),          -- Enlace a la App Store
  telefono          VARCHAR(30),
  whatsapp          VARCHAR(30),
  email             VARCHAR(100),
  direccion         VARCHAR(255),
  facebook          VARCHAR(255),
  instagram         VARCHAR(255),
  tiktok            VARCHAR(255),
  youtube           VARCHAR(255),
  twitter           VARCHAR(255),
  footer_texto      TEXT,                  -- Texto informativo al final de la página
  footer_copyright  VARCHAR(255),          -- Texto de derechos reservados
  meta_titulo       VARCHAR(255),          -- Título optimizado para Google
  meta_descripcion  TEXT,                  -- Descripción optimizada para Google
  creado_en         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Banner Principal: Imágenes rotativas en la página de inicio (Slider).
CREATE TABLE hero_slides (
  id              INT PRIMARY KEY AUTO_INCREMENT,
  imagen          VARCHAR(255) NOT NULL, -- Ruta de la imagen de fondo
  titulo          VARCHAR(200),          -- Título principal sobre el banner
  subtitulo       VARCHAR(255),          -- Texto secundario pequeño
  texto           TEXT,                  -- Descripción adicional
  mostrar_texto   TINYINT(1) DEFAULT 0,  -- Interruptor para mostrar/ocultar texto
  alineacion      ENUM('izquierda','centro','derecha') DEFAULT 'centro',
  orden           INT DEFAULT 0,         -- Prioridad de aparición (0 es primero)
  activo          TINYINT(1) DEFAULT 1,  -- Estado para pausar el slide sin borrarlo
  creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Locutores: Perfiles del staff y presentadores de la emisora.
CREATE TABLE locutores (
  id                INT PRIMARY KEY AUTO_INCREMENT,
  nombre            VARCHAR(150) NOT NULL,
  cargo             VARCHAR(100),          -- Ej: Director, DJ, Periodista
  foto              VARCHAR(255),          -- Imagen de perfil del locutor
  bio               TEXT,                  -- Breve biografía
  instagram_usuario VARCHAR(100),          -- @usuario para mostrar en la web
  instagram_url     VARCHAR(255),          -- Link directo al perfil
  destacado         TINYINT(1) DEFAULT 0,  -- Si aparece en la sección principal
  orden           INT DEFAULT 0,
  activo          TINYINT(1) DEFAULT 1,
  creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Programación: Horarios y días de emisión de los shows.
CREATE TABLE programas (
  id              INT PRIMARY KEY AUTO_INCREMENT,
  titulo          VARCHAR(150) NOT NULL,
  imagen          VARCHAR(255),          -- Poster o arte del programa
  descripcion     TEXT,
  hora_inicio     TIME,
  hora_fin        TIME,
  horario_texto   VARCHAR(100),          -- Ej: "Lunes a Viernes"
  dias            JSON,                  -- Array de días (1-7) en formato JSON
  locutor_id      INT,                   -- Relación con la tabla locutores
  destacado       TINYINT(1) DEFAULT 0,
  orden           INT DEFAULT 0,
  activo          TINYINT(1) DEFAULT 1,
  creado_en       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (locutor_id) REFERENCES locutores(id) ON DELETE SET NULL
);

-- 5/6. Tabla única para el Ranking: Aquí el admin mete las canciones directamente.
CREATE TABLE top5_canciones (
  id          INT PRIMARY KEY AUTO_INCREMENT,
  posicion    INT NOT NULL,              -- Puesto (1, 2, 3, 4, 5)
  titulo      VARCHAR(150) NOT NULL,     -- Nombre de la canción
  artista     VARCHAR(150) NOT NULL,     -- Nombre del artista
  portada     VARCHAR(255),              -- URL de la imagen
  enlace      VARCHAR(255),              -- Link externo
  youtube_url VARCHAR(255),              -- Link de YouTube
  fecha_ranking DATE,                    -- Para saber de qué semana es este top
  activo      TINYINT(1) DEFAULT 1,      -- Permite ocultar una canción si es necesario
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- 7. Blog/Noticias: Artículos, novedades y actualidad musical.
CREATE TABLE noticias (
  id                INT PRIMARY KEY AUTO_INCREMENT,
  titulo            VARCHAR(255) NOT NULL,
  slug              VARCHAR(255) NOT NULL UNIQUE, -- URL amigable (ej: noticia-musical-2024)
  resumen           TEXT,                  -- Texto corto para la vista previa
  contenido         LONGTEXT,              -- Cuerpo completo de la noticia (HTML)
  imagen_portada    VARCHAR(255),          -- Imagen grande interna
  imagen_tarjeta    VARCHAR(255),          -- Miniatura para el listado
  categoria         VARCHAR(255),          -- Ej: Eventos, Entrevistas, Estrenos
  autor             VARCHAR(100),
  destacada         TINYINT(1) DEFAULT 0,  -- Si aparece en la parte superior del blog
  estado            ENUM('borrador','publicado') DEFAULT 'borrador',
  fecha_publicacion DATETIME,
  meta_titulo       VARCHAR(255),
  meta_descripcion  TEXT,
  creado_en         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 8. Video Recomendado: Sección destacada para un video único de YouTube.
CREATE TABLE recomendada (
  id             INT PRIMARY KEY AUTO_INCREMENT,
  titulo_bloque  VARCHAR(150),          -- Título de la sección (ej: "Estreno de la semana")
  titulo_video   VARCHAR(255),          -- Nombre del video
  descripcion    TEXT,
  youtube_url    VARCHAR(255) NOT NULL, -- Link para embeber el video
  activo         TINYINT(1) DEFAULT 1,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 9. Chat: Configuración de módulo externo de interacción en vivo.
CREATE TABLE chat_config (
  id             INT PRIMARY KEY AUTO_INCREMENT,
  titulo         VARCHAR(150),          -- Título del chat (ej: "Chat en Vivo")
  subtitulo      VARCHAR(255),
  embed_code     TEXT,                  -- Código HTML/JS (Iframe) del proveedor de chat
  visible        TINYINT(1) DEFAULT 1,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 10. Usuarios: Gestión de acceso al panel de administración.
CREATE TABLE usuarios (
  id             INT PRIMARY KEY AUTO_INCREMENT,
  nombre         VARCHAR(150) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL, -- Contraseña cifrada
  rol            ENUM('admin','editor') DEFAULT 'editor', -- Nivel de permisos
  activo         TINYINT(1) DEFAULT 1,
  ultimo_acceso  DATETIME,
  creado_en      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);