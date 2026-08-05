"""
app.py — Punto de entrada. Solo orquesta: login -> sidebar -> despacho de vista.
Ninguna lógica de negocio vive aquí.
"""

import streamlit as st

# 1. Configuración de página SIEMPRE debe ser el primer comando
st.set_page_config(page_title="AGRIDASH - CONTROL DE FINCA", page_icon="🌱", layout="wide")

from vistas import (
    registro_produccion, gestion_usuarios, configuracion, 
    administracion_ubicaciones, agronomia, rendimiento_colaboradores, administracion_roles
)
from seguridad.auth import cerrar_sesion, mostrar_login
from seguridad.rbac_config import obtener_menu_por_rol

# 2. Estilos Globales Modernos
def aplicar_estilo_moderno():
    st.markdown("""
        <style>
        /* FORZAR VISIBILIDAD DE LA BARRA SUPERIOR */
        header, [data-testid="stHeader"], [data-testid="stToolbar"], #MainMenu {
            visibility: visible !important;
            display: flex !important;
        }

        footer { visibility: hidden; }

        /* Tipografía general */
        html, body, [class*="css"] {
            font-family: 'Inter', 'Segoe UI', -apple-system, sans-serif;
        }

        /* Botones primarios */
        .stButton>button[kind="primary"] {
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.25s ease;
        }
        .stButton>button[kind="primary"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
        }
        
        /* Botones secundarios */
        .stButton>button[kind="secondary"] {
            border-radius: 8px;
            font-weight: 500;
            border: 1px solid transparent;
        }
        .stButton>button[kind="secondary"]:hover {
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        /* Tarjetas, Contenedores y Formularios */
        [data-testid="stForm"], [data-testid="stVerticalBlock"] > div > div[style*="border"] {
            border-radius: 12px !important;
            border: 1px solid var(--secondary-background-color) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            padding: 1.5rem !important;
            background-color: var(--secondary-background-color);
        }

        /* Entradas de texto y selects */
        .stTextInput input, .stSelectbox div[data-baseweb="select"], .stNumberInput input {
            border-radius: 8px !important;
        }

        /* Sidebar - Logo y Marca */
        .sidebar-brand {
            display: flex; align-items: center; gap: 10px;
            padding: 4px 0 16px 0;
        }
        .sidebar-brand .icon { font-size: 1.6rem; line-height: 1; }
        .sidebar-brand .name { font-size: 1.3rem; font-weight: 800; color: var(--primary-color); letter-spacing: .5px; }

        /* ====================================================
           NUEVO DISEÑO DE LA TARJETA DE USUARIO (USER CARD)
           ==================================================== */
        .user-card {
            background-color: var(--secondary-background-color);
            color: var(--text-color);
            padding: 16px;
            border-radius: 16px;
            border: 1px solid rgba(128, 128, 128, 0.18);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.3s ease;
        }
        .user-card:hover {
            border-color: rgba(46, 125, 50, 0.4);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        /* Avatar contenedor e indicador en vivo */
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        .user-card .avatar {
            width: 46px; 
            height: 46px; 
            min-width: 46px; 
            border-radius: 50%;
            background: linear-gradient(135deg, #2e7d32 0%, #66bb6a 100%);
            color: #ffffff;
            display: flex; 
            align-items: center; 
            justify-content: center;
            font-weight: 700; 
            font-size: 1.1rem;
            box-shadow: 0 3px 8px rgba(46, 125, 50, 0.35);
        }
        .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 11px;
            height: 11px;
            background-color: #00e676;
            border: 2px solid var(--secondary-background-color);
            border-radius: 50%;
        }

        /* Info del usuario */
        .user-card .info { 
            line-height: 1.25; 
            overflow: hidden; 
            width: 100%;
        }
        .user-card .label {
            font-size: 10.5px; 
            text-transform: uppercase; 
            letter-spacing: .6px;
            font-weight: 600;
            opacity: 0.6; 
            color: var(--text-color);
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .user-card .name {
            font-weight: 700; 
            color: var(--text-color); 
            font-size: 1rem;
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis;
            margin-bottom: 4px;
        }
        
        /* Insignia de Rol moderna */
        .role-badge {
            display: inline-flex;
            align-items: center;
            background-color: rgba(46, 125, 50, 0.12);
            color: #2e7d32;
            border: 1px solid rgba(46, 125, 50, 0.3);
            padding: 3px 10px; 
            border-radius: 20px;
            font-size: 10px; 
            font-weight: 700; 
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .nav-label {
            font-size: 11px; text-transform: uppercase; letter-spacing: .8px;
            font-weight: 700; opacity: 0.5; margin: 6px 0 8px 2px; color: var(--text-color);
        }
        </style>
    """, unsafe_allow_html=True)

aplicar_estilo_moderno()

# 3. Registro de Vistas
VISTAS = {
    "registro_produccion": registro_produccion.render,
    "rendimiento_colaboradores": rendimiento_colaboradores.render,
    "administracion_roles": administracion_roles.vista_administracion_roles,
    "configuracion": configuracion.render,
    "agronomia": agronomia.render,
    "gestion_usuarios": gestion_usuarios.render,
    "administracion_ubicaciones": administracion_ubicaciones.render,
}

# 4. Lógica de Autenticación
if not st.session_state.get("autenticado", False):
    mostrar_login()
    st.stop()

# Navegación en memoria
if "vista_actual" not in st.session_state:
    st.session_state["vista_actual"] = None

# 5. Lógica de Sesión Activa (Sidebar)
with st.sidebar:
    st.markdown("""
        <div class='sidebar-brand'>
            <span class='icon'>🌱</span><span class='name'>AGRIDASH</span>
        </div>
    """, unsafe_allow_html=True)

    rol = st.session_state.get("rol", "SIN ROL")
    nombre = st.session_state.get("nombre", "Usuario")
    iniciales = "".join(p[0] for p in nombre.split()[:2]).upper() or "?"

    # TARJETA DE USUARIO RENOVADA
    st.markdown(f"""
        <div class='user-card'>
            <div class='avatar-container'>
                <div class='avatar'>{iniciales}</div>
                <div class='status-dot' title='En línea'></div>
            </div>
            <div class='info'>
                <div class='label'>● En Línea</div>
                <div class='name'>{nombre}</div>
                <span class='role-badge'>🛡️ {rol}</span>
            </div>
        </div>
    """, unsafe_allow_html=True)

    opciones_menu = obtener_menu_por_rol(rol)
    etiquetas = [op[0] for op in opciones_menu]

    if etiquetas:
        st.markdown("<div class='nav-label'>Módulos de Navegación</div>", unsafe_allow_html=True)
        
        for etiqueta in etiquetas:
            tipo_boton = "primary" if st.session_state["vista_actual"] == etiqueta else "secondary"
            if st.button(etiqueta, type=tipo_boton, use_container_width=True):
                st.session_state["vista_actual"] = etiqueta
                st.rerun()
    else:
        st.warning(f"Tu rol ({rol}) no tiene módulos asignados aún.")

    st.markdown("---")
    
    if st.button("🚪 CERRAR SESIÓN", type="secondary", use_container_width=True):
        cerrar_sesion()
        st.rerun()

# 6. Despacho
eleccion = st.session_state["vista_actual"]

if eleccion and eleccion in dict(opciones_menu):
    clave_modulo = dict(opciones_menu)[eleccion]
    funcion_vista = VISTAS.get(clave_modulo)
    
    if funcion_vista:
        funcion_vista()
    else:
        st.info(f"El módulo '{clave_modulo}' todavía no tiene vista implementada.")
else:
    st.write("")
    st.write("")
    st.markdown(f"<h1 style='text-align: center; color: var(--primary-color);'>👋 ¡Bienvenido a AgriDash, {nombre}!</h1>", unsafe_allow_html=True)
    st.markdown("<h4 style='text-align: center; color: gray; font-weight: normal;'>Selecciona un módulo en el menú lateral izquierdo para comenzar.</h4>", unsafe_allow_html=True)
    
    if not etiquetas:
        st.error("⚠️ **Atención:** Tu usuario actual no tiene ningún permiso configurado. Comunícate con el Administrador para asignar permisos.")