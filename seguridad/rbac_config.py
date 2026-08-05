"""
seguridad/rbac_config.py
=========================
Fuente dinámicamente cargada de permisos y menús desde Google Sheets.
"""

import streamlit as st
from modelos.modelo_roles import ModeloRoles

MODULOS_SISTEMA = {
    "vista_gerencial": "📊 VISIÓN GERENCIAL",
    "rendimiento_colaboradores": "⏱️ RENDIMIENTO",
    "gestion_usuarios": "👥 GESTIÓN DE USUARIOS",
    "registro_produccion": "👨‍🌾 REGISTRO DE PRODUCCIÓN",
    "agronomia": "🔬 AGRONOMÍA",
    "administracion_ubicaciones": "📍 UBICACIONES",
    "administracion_roles": "🛡️ GESTIÓN DE ROLES",
    "configuracion": "⚙️ CONFIGURACIÓN Y SEGURIDAD",
}

@st.cache_data(ttl=60)
def cargar_matriz_permisos() -> dict:
    """Obtiene la matriz de permisos actualizada desde la base de datos."""
    modelo = ModeloRoles()
    matriz = modelo.obtener_matriz_permisos()
    
    # Fallback/Respaldo si la hoja de base de datos está vacía
    if not matriz:
        matriz = {
            "ADMIN": {m: ["ver", "crear", "editar", "eliminar"] for m in MODULOS_SISTEMA},
            "SUPERADMIN": {m: ["ver", "crear", "editar", "eliminar"] for m in MODULOS_SISTEMA}
        }
    return matriz


def tiene_permiso(rol: str, modulo: str, accion: str = "ver") -> bool:
    """Verifica permisos consultando la matriz dinámica."""
    permisos = cargar_matriz_permisos()
    return accion in permisos.get(rol, {}).get(modulo, [])


def obtener_menu_por_rol(rol: str) -> list[tuple[str, str]]:
    """Genera dinámicamente el menú lateral según las pestañas a las que el rol tiene acceso 'ver'."""
    permisos = cargar_matriz_permisos()
    permisos_rol = permisos.get(rol, {})

    menu = []
    for clave_mod, etiqueta in MODULOS_SISTEMA.items():
        if "ver" in permisos_rol.get(clave_mod, []):
            menu.append((etiqueta, clave_mod))
    return menu