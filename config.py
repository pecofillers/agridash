"""
config.py
=========
Configuración centralizada de AgriDash. Ningún otro módulo debe tener
rutas o "números mágicos" escritos a mano: todos se importan de aquí.
"""

import os

# ------------------------------------------------------------------
# Rutas
# ------------------------------------------------------------------
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(BASE_DIR, "data")
os.makedirs(DATA_DIR, exist_ok=True)

# Nombres canónicos de las tablas (usados por los modelos)
ARCHIVO_USUARIOS = "dim_usuarios.xlsx"
ARCHIVO_UBICACIONES = "dim_ubicaciones.xlsx"
ARCHIVO_PRODUCCION = "fact_produccion.xlsx"

# ------------------------------------------------------------------
# Seguridad
# ------------------------------------------------------------------
MAX_INTENTOS_FALLIDOS = 5          # Bloqueo por fuerza bruta
MINUTOS_BLOQUEO = 15               # Duración del bloqueo tras superar el máximo
MINUTOS_INACTIVIDAD = 30           # Timeout de sesión por inactividad

# Clave de cifrado de cookies de sesión. EN PRODUCCIÓN debe venir de una
# variable de entorno / secrets.toml, NUNCA quedar hardcodeada en el repo.
COOKIE_NAME = "agridash_sesion_segura"
COOKIE_KEY = os.environ.get("AGRIDASH_COOKIE_KEY", "CAMBIA_ESTA_CLAVE_EN_PRODUCCION")
COOKIE_EXPIRY_DAYS = 1

# Roles válidos del sistema (fuente única de verdad para validaciones)
ROLES_VALIDOS = {"ADMIN", "OPERARIO", "AGRONOMO", "SUPERADMIN"}
