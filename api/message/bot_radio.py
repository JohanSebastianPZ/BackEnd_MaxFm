import json
import time
import re
import os
from collections import Counter
from filelock import FileLock 

# 1. RUTA MÁGICA ABSOLUTA
DIR_ACTUAL = os.path.dirname(os.path.abspath(__file__))

ARCHIVO_MENSAJES    = os.path.join(DIR_ACTUAL, 'chat_messages.json')
ARCHIVO_ESTADISTICAS = os.path.join(DIR_ACTUAL, 'chat_stats.json')
ARCHIVO_HEARTBEAT   = os.path.join(DIR_ACTUAL, 'bot_heartbeat.json')
LOCK_FILE           = os.path.join(DIR_ACTUAL, 'chat_messages.json.lock')

PALABRAS_PROHIBIDAS = [
    # General / España
    "puta", "puto", "putas", "putos", "mierda", "mierdas", "joder", "cabron", "cabrón", "cabrones", 
    "gilipollas", "subnormal", "capullo", "cojones", "coño", "hostia", "hostias", "polla", "maricon", 
    "maricón", "follón", "follar", "zorra", "perra", "ramera", "putita", "putito", "putón",

    # México / Centroamérica
    "pendejo", "pendeja", "pendejos", "pinche", "chingar", "chingada", "chinga", "chingón", 
    "verga", "pito", "culero", "culeros", "mamada", "mamadas", "wey", "güey", "joto",

    # Sudamérica (Colombia, Argentina, Chile, Perú, etc.)
    "pelotudo", "boludo", "forro", "concha", "chucha", "wea", "huevada", "huevon", "huevón", 
    "weon", "weón", "culiao", "culiado", "conchatumare", "hijueputa", "hijo de puta", "hdp", 
    "malparido", "gonorrea", "pirobo", "mamaguevo", "mamaguevo", "ctm", "csm"
]

# (Opcional pero recomendado) Palabras comunes que no queremos en "Palabras Virales"
PALABRAS_IGNORADAS = ["este", "esta", "para", "como", "pero", "todo", "toda"]

def procesar_chat():
    if not os.path.exists(ARCHIVO_MENSAJES):
        print("Aún no hay mensajes. Esperando a que PHP cree el archivo...")
        return

    lock = FileLock(LOCK_FILE, timeout=2) 
    try:
        with lock:
            with open(ARCHIVO_MENSAJES, 'r', encoding='utf-8') as f:
                mensajes = json.load(f)
            
            hubo_cambios = False
            
            if len(mensajes) > 200:
                mensajes = mensajes[-200:] 
                hubo_cambios = True

            usuarios_activos = Counter()
            palabras_totales = Counter()
            peticiones_canciones = []
            ubicaciones = []

            for msg in mensajes:
                texto = msg.get('text', '')
                usuario = msg.get('user', 'Anónimo')

                # --- Si el mensaje YA fue moderado antes, lo saltamos por completo ---
                if "🚫 Este mensaje ha sido moderado" in texto:
                    continue

                texto_lower = texto.lower()
                usuario_lower = usuario.lower()

                # --- 2. MODERACIÓN DE USUARIO ---
                if any(mala_palabra in usuario_lower for mala_palabra in PALABRAS_PROHIBIDAS):
                    if msg['user'] != "Usuario Moderado":
                        msg['user'] = "Usuario Moderado"
                        usuario = "Usuario Moderado" 
                        hubo_cambios = True

                # --- 3. MODERACIÓN DE TEXTO INTELIGENTE ---
                es_texto_inapropiado = False
                for mala_palabra in PALABRAS_PROHIBIDAS:
                    if re.search(r'\b' + re.escape(mala_palabra) + r'\b', texto_lower):
                        es_texto_inapropiado = True
                        break
                
                if es_texto_inapropiado:
                    if msg['text'] != "🚫 Este mensaje ha sido moderado.":
                        msg['text'] = "🚫 Este mensaje ha sido moderado."
                        hubo_cambios = True
                    continue 

                # --- ANÁLISIS ---
                
                # Solo contamos al usuario si NO es "Usuario Moderado"
                if usuario != "Usuario Moderado":
                    usuarios_activos[usuario] += 1
                
                # Extraemos palabras de +4 letras
                palabras = re.findall(r'\b\w{4,}\b', texto_lower) 
                
                # (Extra) Filtramos palabras aburridas para que no ensucien tus "Palabras Virales"
                palabras_limpias = [p for p in palabras if p not in PALABRAS_IGNORADAS]
                palabras_totales.update(palabras_limpias)

                if any(p in texto_lower for p in ["pon", "canción", "temazo", "suena", "compláceme"]):
                    peticiones_canciones.append({"usuario": usuario, "texto": texto})

                if "desde" in texto_lower or "saludos a" in texto_lower:
                    ubicaciones.append({"usuario": usuario, "texto": texto})

            if hubo_cambios:
                with open(ARCHIVO_MENSAJES, 'w', encoding='utf-8') as f:
                    json.dump(mensajes, f, ensure_ascii=False, indent=2)

        estadisticas = {
            "top_usuarios": usuarios_activos.most_common(5),
            "top_palabras": palabras_totales.most_common(10),
            "peticiones": peticiones_canciones[-10:],
            "ubicaciones": ubicaciones[-10:]
        }

        with open(ARCHIVO_ESTADISTICAS, 'w', encoding='utf-8') as f:
            json.dump(estadisticas, f, ensure_ascii=False, indent=2)

    except Exception as e:
        print(f"Error procesando: {e}")

def escribir_heartbeat():
    """Escribe un timestamp cada iteración para que el dashboard sepa que el bot está vivo."""
    try:
        with open(ARCHIVO_HEARTBEAT, 'w', encoding='utf-8') as f:
            json.dump({"ts": time.time(), "hora": time.strftime('%H:%M:%S')}, f)
    except Exception as e:
        print(f"Error escribiendo heartbeat: {e}")

if __name__ == "__main__":
    print("Iniciando Bot de Moderación y Análisis de MAX FM...")
    while True:
        escribir_heartbeat()   # siempre, antes de procesar
        procesar_chat()
        time.sleep(3)