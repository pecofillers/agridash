from datetime import datetime, timedelta
from functools import wraps
import bcrypt
import pandas as pd
import streamlit as st

from config import MAX_INTENTOS_FALLIDOS, MINUTOS_BLOQUEO, MINUTOS_INACTIVIDAD
from modelos.modelo_usuarios import tabla_usuarios, obtener_usuario
from seguridad.rbac_config import cargar_matriz_permisos

# ------------------------------------------------------------------
# A) Control de Permisos RBAC (Dinámico y Flexible)
# ------------------------------------------------------------------
def tiene_permiso(rol_o_id, modulo: str, accion: str = "ver") -> bool:
    """
    Consulta la matriz RBAC dinámica. Es totalmente flexible:
    acepta tanto el ID numérico del rol como su nombre en texto.
    """
    permisos = cargar_matriz_permisos()
    
    # Buscamos directamente si la clave existe (puede ser ID numérico o texto)
    acciones_rol = permisos.get(rol_o_id)
    
    # Si no se encuentra directo, intentamos buscar si pasaron el nombre en lugar de ID o viceversa
    if not acciones_rol:
        for k, v in permisos.items():
            if str(k).lower() == str(rol_o_id).lower():
                acciones_rol = v
                break
                
    if not acciones_rol:
        return False
        
    return accion in acciones_rol.get(modulo, [])


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
    contra la base de datos si tiene acceso al módulo indicado.
    """
    def decorador(func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            rol_actual = st.session_state.get("rol")
            
            if not tiene_permiso(rol_actual, modulo, accion):
                st.error(f"⛔ Tu rol actual (**{rol_actual}**) no tiene permisos configurados para acceder a este módulo.")
                st.stop()
            return func(*args, **kwargs)
        return wrapper
    return decorador


# ------------------------------------------------------------------
# B) Control de Inactividad (Timeout de sesión)
# ------------------------------------------------------------------
def _sesion_vigente() -> bool:
    ultima = st.session_state.get("ultima_actividad")
    if ultima is None:
        return True
    return (datetime.now() - ultima) < timedelta(minutes=MINUTOS_INACTIVIDAD)


def _actualizar_ultima_actividad():
    st.session_state["ultima_actividad"] = datetime.now()


def cerrar_sesion():
    for clave in ("autenticado", "username", "nombre", "rol", "rol_id", "ultima_actividad"):
        st.session_state.pop(clave, None)


# ------------------------------------------------------------------
# C) Hashing de Contraseñas (bcrypt)
# ------------------------------------------------------------------
def hash_password(password_plano: str) -> str:
    """Usar SIEMPRE esta función al crear/resetear una contraseña de usuario."""
    return bcrypt.hashpw(password_plano.encode("utf-8"), bcrypt.gensalt()).decode("utf-8")


def _verificar_password(password_plano: str, password_hash: str) -> bool:
    try:
        return bcrypt.checkpw(password_plano.encode("utf-8"), str(password_hash).encode("utf-8"))
    except (ValueError, TypeError):
        return False


# ------------------------------------------------------------------
# D) Bloqueo por Fuerza Bruta
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
    if df is None or df.empty:
        return
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
    if df is None or df.empty:
        return
    idx = df.index[df["Username"] == username]
    if idx.empty:
        return
    i = idx[0]
    df.at[i, "Intentos_Fallidos"] = 0
    df.at[i, "Bloqueado_Hasta"] = None
    tabla_usuarios.actualizar_todo(df)


# ------------------------------------------------------------------
# E) Lógica de Autenticación (Backend)
# ------------------------------------------------------------------
def login(username: str, password: str) -> tuple[bool, str]:
    """
    Intenta autenticar. Devuelve (exito, mensaje).
    Si exito=True, guarda la sesión en st.session_state.
    """
    username = (username or "").strip().lower()
    usuario = obtener_usuario(username)

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
    
    # Guardamos tanto el nombre del rol como el ID numérico para máxima compatibilidad
    st.session_state["rol"] = usuario.get("Nombre_Rol", usuario.get("ID_Rol"))
    st.session_state["rol_id"] = usuario.get("ID_Rol")
    
    _actualizar_ultima_actividad()
    return True, f"✅ Bienvenido, {usuario.get('Nombre', username)}."


# ------------------------------------------------------------------
# F) Vista Visual del Login (Frontend Centrado y Moderno)
# ------------------------------------------------------------------
def mostrar_login():
    """
    Muestra la interfaz de Inicio de Sesión estilo Tarjeta Centrada.
    Mantiene el modo claro y oscuro dinámico mediante variables de Streamlit.
    """
    col1, col2, col3 = st.columns([1, 1.5, 1])
    
    with col2:
        st.write("")
        st.write("")
        
        # Encabezado moderno
        st.markdown("<h1 style='text-align: center; margin-bottom: 0px;'>🌱 AgriDash</h1>", unsafe_allow_html=True)
        st.markdown("<p style='text-align: center; color: gray; margin-bottom: 25px;'>Sistema de Gestión • Ecofillers</p>", unsafe_allow_html=True)
        
        # Contenedor / Tarjeta del Formulario
        with st.form("form_login_moderno"):
            usuario_input = st.text_input("👤 Usuario", placeholder="Ingresa tu username").strip()
            clave_input = st.text_input("🔒 Contraseña", type="password", placeholder="••••••••")
            
            st.write("")
            btn_ingresar = st.form_submit_button("INICIAR SESIÓN", type="primary", use_container_width=True)
            
            if btn_ingresar:
                if not usuario_input or not clave_input:
                    st.error("⚠️ Ingrese usuario y contraseña.")
                else:
                    exito, mensaje = login(usuario_input, clave_input)
                    if exito:
                        st.success(mensaje)
                        st.rerun()
                    else:
                        st.error(mensaje)