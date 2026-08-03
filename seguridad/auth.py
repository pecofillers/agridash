from datetime import datetime, timedelta
from functools import wraps

import bcrypt
import pandas as pd
import streamlit as st

from config import MAX_INTENTOS_FALLIDOS, MINUTOS_BLOQUEO, MINUTOS_INACTIVIDAD
from modelos.modelo_usuarios import tabla_usuarios, obtener_usuario
from seguridad.rbac_config import tiene_permiso
from seguridad.rbac_config import cargar_matriz_permisos

# ------------------------------------------------------------------
# A) RBAC
# ------------------------------------------------------------------
def tiene_permiso(rol: str, modulo: str, accion: str = "ver") -> bool:
    """Consulta la matriz RBAC dinámica (Google Sheets)."""
    # 👇 CAMBIO AQUÍ: Cargamos los permisos en vivo
    permisos = cargar_matriz_permisos()
    return accion in permisos.get(rol, {}).get(modulo, [])

# ... (El resto de tu auth.py desde requiere_autenticacion hacia abajo se queda IGUAL) ...
def requiere_autenticacion(func):
    """
    Decorador de vista: bloquea el acceso directo a un módulo (Forced Browsing)
    si no hay sesión activa o si la sesión expiró por inactividad.
    """
    @wraps(func)
    def wrapper(*args, **kwargs):
        if not st.session_state.get("autenticado", False):
            st.error("🔒 Debes iniciar sesión para acceder a este módulo.")
            st.stop()
        if not _sesion_vigente():
            cerrar_sesion()
            st.warning("⏳ Tu sesión expiró por inactividad. Ingresa de nuevo.")
            st.stop()
        _actualizar_ultima_actividad()
        return func(*args, **kwargs)
    return wrapper


def requiere_permiso(modulo: str, accion: str = "ver"):
    """
    Decorador dinámico: Lee el rol del usuario en sesión y verifica
    contra la base de datos (Google Sheets) si tiene acceso al módulo.
    """
    def decorador(func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            rol_actual = st.session_state.get("rol")
            
            # Consultamos la base de datos dinámica en vivo
            if not tiene_permiso(rol_actual, modulo, accion):
                st.error(f"⛔ Tu rol actual (**{rol_actual}**) no tiene permisos configurados en la base de datos para acceder a este módulo.")
                st.stop()
                
            return func(*args, **kwargs)
        return wrapper
    return decorador


# ------------------------------------------------------------------
# B) Control de inactividad (timeout de sesión)
# ------------------------------------------------------------------
def _sesion_vigente() -> bool:
    ultima = st.session_state.get("ultima_actividad")
    if ultima is None:
        return True
    return (datetime.now() - ultima) < timedelta(minutes=MINUTOS_INACTIVIDAD)


def _actualizar_ultima_actividad():
    st.session_state["ultima_actividad"] = datetime.now()


def cerrar_sesion():
    for clave in ("autenticado", "username", "nombre", "rol", "ultima_actividad"):
        st.session_state.pop(clave, None)


# ------------------------------------------------------------------
# Hashing de contraseñas (bcrypt — nunca texto plano)
# ------------------------------------------------------------------
def hash_password(password_plano: str) -> str:
    """Usar SIEMPRE esta función al crear/resetear una contraseña de usuario."""
    return bcrypt.hashpw(password_plano.encode("utf-8"), bcrypt.gensalt()).decode("utf-8")


def _verificar_password(password_plano: str, password_hash: str) -> bool:
    try:
        return bcrypt.checkpw(password_plano.encode("utf-8"), str(password_hash).encode("utf-8"))
    except (ValueError, TypeError):
        # Hash corrupto o vacío: nunca autenticar por error, solo negar.
        return False


# ------------------------------------------------------------------
# B) Bloqueo por fuerza bruta
# ------------------------------------------------------------------
def _usuario_bloqueado(usuario: dict) -> bool:
    bloqueado_hasta = usuario.get("Bloqueado_Hasta")
    if bloqueado_hasta is None or pd.isna(bloqueado_hasta) or str(bloqueado_hasta) in ("", "None", "NaT"):
        return False
    try:
        return datetime.now() < pd.to_datetime(bloqueado_hasta)
    except Exception:
        return False


def _registrar_intento_fallido(username: str):
    df = tabla_usuarios.leer()
    idx = df.index[df["Username"] == username]
    if idx.empty:
        return
    i = idx[0]
    intentos = int(pd.to_numeric(df.at[i, "Intentos_Fallidos"], errors="coerce") or 0) + 1
    df.at[i, "Intentos_Fallidos"] = intentos
    if intentos >= MAX_INTENTOS_FALLIDOS:
        df.at[i, "Bloqueado_Hasta"] = datetime.now() + timedelta(minutes=MINUTOS_BLOQUEO)
    tabla_usuarios.actualizar_todo(df)


def _resetear_intentos(username: str):
    df = tabla_usuarios.leer()
    idx = df.index[df["Username"] == username]
    if idx.empty:
        return
    i = idx[0]
    df.at[i, "Intentos_Fallidos"] = 0
    df.at[i, "Bloqueado_Hasta"] = None
    tabla_usuarios.actualizar_todo(df)


# ------------------------------------------------------------------
# Login
# ------------------------------------------------------------------
def login(username: str, password: str) -> tuple[bool, str]:
    """
    Intenta autenticar. Devuelve (exito, mensaje).
    Si exito=True, guarda la sesión en st.session_state.
    """
    username = (username or "").strip().lower()
    usuario = obtener_usuario(username)

    # Mensaje genérico e idéntico exista o no el usuario: mitiga user enumeration.
    mensaje_generico = "❌ Usuario o contraseña incorrectos."

    if usuario is None:
        return False, mensaje_generico

    if usuario.get("Estado") != "ACTIVO":
        return False, "🚫 Esta cuenta está deshabilitada. Contacta al administrador."

    if _usuario_bloqueado(usuario):
        return False, f"🔒 Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intenta de nuevo en {MINUTOS_BLOQUEO} minutos."

    if not _verificar_password(password, usuario.get("Password_Hash", "")):
        _registrar_intento_fallido(username)
        return False, mensaje_generico

    _resetear_intentos(username)
    st.session_state["autenticado"] = True
    st.session_state["username"] = username
    st.session_state["nombre"] = usuario.get("Nombre", username)
    st.session_state["rol"] = usuario.get("ID_Rol")
    _actualizar_ultima_actividad()
    return True, f"✅ Bienvenido, {usuario.get('Nombre', username)}."

def requiere_permiso(modulo: str, accion: str = "ver"):
    """
    Decorador dinámico: Lee el rol del usuario en sesión y verifica
    contra la base de datos (Google Sheets) si tiene acceso al módulo.
    """
    def decorador(func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            rol_actual = st.session_state.get("rol")
            
            # Consultamos la base de datos dinámica en vivo
            if not tiene_permiso(rol_actual, modulo, accion):
                st.error(f"⛔ Tu rol actual (**{rol_actual}**) no tiene permisos configurados en la base de datos para acceder a este módulo.")
                st.stop()
                
            return func(*args, **kwargs)
        return wrapper
    return decorador