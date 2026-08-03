"""
app.py — Punto de entrada. Solo orquesta: login -> sidebar -> despacho de vista.
Ninguna lógica de negocio vive aquí.
"""

import streamlit as st

from vistas import registro_produccion, gestion_usuarios, configuracion, administracion_ubicaciones, agronomia, rendimiento_colaboradores
from seguridad.auth import login, cerrar_sesion
from seguridad.rbac_config import obtener_menu_por_rol


# Importación de las vistas
from vistas import registro_produccion
# Importa aquí tu nuevo módulo de roles cuando lo guardes
from vistas import administracion_roles

st.set_page_config(page_title="AGRIDASH - CONTROL DE FINCA", page_icon="🌱", layout="wide")

st.markdown("""
    <style>
    /* Todo se apoya en las variables de tema de Streamlit (--background-color,
       --secondary-background-color, --text-color, --primary-color) en vez de
       colores fijos, para que se vea bien tanto en modo Light como Dark. */

    .main-title { font-size: 2.2rem; color: var(--primary-color); font-weight: 700; }

    .sidebar-brand {
        display: flex; align-items: center; gap: 10px;
        padding: 4px 0 18px 0;
    }
    .sidebar-brand .icon { font-size: 1.6rem; line-height: 1; }
    .sidebar-brand .name { font-size: 1.3rem; font-weight: 700; color: var(--primary-color); letter-spacing: .3px; }

    .user-card {
        background-color: var(--secondary-background-color);
        color: var(--text-color);
        padding: 14px 16px;
        border-radius: 12px;
        border: 1px solid rgba(128,128,128,0.25);
        border-left: 4px solid var(--primary-color);
        margin-bottom: 18px;
        display: flex; align-items: center; gap: 12px;
    }
    .user-card .avatar {
        width: 40px; height: 40px; min-width: 40px; border-radius: 50%;
        background-color: var(--primary-color); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 1rem;
    }
    .user-card .info { line-height: 1.35; overflow: hidden; }
    .user-card .label {
        font-size: 11px; text-transform: uppercase; letter-spacing: .5px;
        opacity: 0.6; color: var(--text-color);
    }
    .user-card .name {
        font-weight: 600; color: var(--text-color); font-size: 0.95rem;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .role-badge {
        display: inline-block; margin-top: 3px;
        background-color: var(--primary-color); color: #fff;
        padding: 2px 9px; border-radius: 10px;
        font-size: 10.5px; font-weight: 600; letter-spacing: .4px;
    }

    .nav-label {
        font-size: 11px; text-transform: uppercase; letter-spacing: .6px;
        opacity: 0.55; margin: 4px 0 6px 2px; color: var(--text-color);
    }
    </style>
""", unsafe_allow_html=True)

# Registro de vistas disponibles. Agregar una vista nueva = una línea aquí.
VISTAS = {
    "registro_produccion": registro_produccion.render,
    # Habilitamos la nueva vista de roles dinámicos
    "rendimiento_colaboradores": rendimiento_colaboradores.render,
    "administracion_roles": administracion_roles.vista_administracion_roles,
    "configuracion": configuracion.render,
    # "vista_gerencial": vista_gerencial.render,
    "agronomia": agronomia.render,
    "gestion_usuarios": gestion_usuarios.render,
    "administracion_ubicaciones": administracion_ubicaciones.render,
}

if not st.session_state.get("autenticado", False):
    st.markdown("<h1 class='main-title' style='text-align:center;'>🌱 AGRIDASH - ACCESO SEGURO</h1>", unsafe_allow_html=True)
    with st.form("form_login"):
        username = st.text_input("Usuario")
        password = st.text_input("Contraseña", type="password")
        if st.form_submit_button("Ingresar", type="primary"):
            exito, mensaje = login(username, password)
            (st.success if exito else st.error)(mensaje)
            if exito:
                st.rerun()
    st.stop()

# --- Sesión activa: sidebar + despacho ---
with st.sidebar:
    st.markdown("""
        <div class='sidebar-brand'>
            <span class='icon'>🌱</span><span class='name'>AGRIDASH</span>
        </div>
    """, unsafe_allow_html=True)

    rol = st.session_state["rol"]
    nombre = st.session_state["nombre"]
    iniciales = "".join(p[0] for p in nombre.split()[:2]).upper() or "?"

    st.markdown(f"""
        <div class='user-card'>
            <div class='avatar'>{iniciales}</div>
            <div class='info'>
                <div class='label'>Usuario activo</div>
                <div class='name'>{nombre}</div>
                <span class='role-badge'>{rol}</span>
            </div>
        </div>
    """, unsafe_allow_html=True)

    # El menú se obtiene ahora de forma dinámica desde Google Sheets
    opciones_menu = obtener_menu_por_rol(rol)
    etiquetas = [op[0] for op in opciones_menu]

    eleccion = None
    if etiquetas:
        st.markdown("<div class='nav-label'>Navegación</div>", unsafe_allow_html=True)
        eleccion = st.radio("IR A:", etiquetas, label_visibility="collapsed")
    else:
        # Mensaje actualizado a la nueva arquitectura
        st.warning(
            f"El rol **{rol}** no tiene módulos asignados en la base de datos "
            f"(dim_permisos_rol). Pide a un administrador que asigne permisos a tu rol."
        )

    st.markdown("---")
    if st.button("🚪 CERRAR SESIÓN", use_container_width=True):
        cerrar_sesion()
        st.rerun()

if eleccion:
    # Mapea la selección visual con la clave del módulo interno
    clave_modulo = dict(opciones_menu)[eleccion]
    funcion_vista = VISTAS.get(clave_modulo)
    
    if funcion_vista:
        funcion_vista()
    else:
        st.info(f"El módulo '{clave_modulo}' todavía no tiene vista implementada en este avance.")