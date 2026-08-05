import streamlit as st
from modelos.db_core import DBCore

# Diccionario maestro con los módulos oficiales del sistema
MODULOS_SISTEMA = {
    "registro_produccion": "📝 Registro de Producción",
    "rendimiento_colaboradores": "⏱️ Rendimiento de Labor",
    "administracion_roles": "🛡️ Roles y Permisos",
    "configuracion": "⚙️ Configuración General",
    "agronomia": "🌾 Módulo de Agronomía",
    "gestion_usuarios": "👥 Gestión de Usuarios",
    "administracion_ubicaciones": "📍 Ubicaciones (Bloques/Naves)"
}

class ModeloRBAC(DBCore):
    def __init__(self):
        super().__init__()

    def obtener_permisos_rol(self, rol_o_id):
        """
        Obtiene los permisos de un rol buscando tanto por su ID numérico 
        como por su nombre textual en dim_roles.
        """
        if rol_o_id is None:
            return {}

        # 1. Intentamos determinar el ID numérico real del rol en la base de datos
        query_id = """
            SELECT ID_Rol FROM dim_roles 
            WHERE ID_Rol = :val OR Nombre_Rol = :val 
            LIMIT 1
        """
        res = self.leer_datos(query_id, {"val": str(rol_o_id)})
        
        if res.empty:
            # Si no encuentra el rol, por seguridad absoluta retornamos permisos vacíos
            return {}
            
        id_rol_num = res.iloc[0]["ID_Rol"]

        # 2. Consultamos los permisos usando el ID numérico seguro
        query = """
            SELECT Modulo, Permiso_Ver, Permiso_Crear, Permiso_Editar, Permiso_Eliminar
            FROM dim_permisos_rol
            WHERE ID_Rol = :rol_id
        """
        df = self.leer_datos(query, {"rol_id": id_rol_num})
        
        permisos = {}
        if df is not None and not df.empty:
            for _, row in df.iterrows():
                acciones = []
                if row["Permiso_Ver"]: acciones.append("ver")
                if row["Permiso_Crear"]: acciones.append("crear")
                if row["Permiso_Editar"]: acciones.append("editar")
                if row["Permiso_Eliminar"]: acciones.append("eliminar")
                
                permisos[row["Modulo"]] = acciones
        return permisos

db_rbac = ModeloRBAC()

def cargar_matriz_permisos():
    """Carga la matriz completa mapeando tanto nombres como IDs de roles."""
    query = "SELECT ID_Rol, Nombre_Rol FROM dim_roles"
    df_roles = db_rbac.leer_datos(query)
    matriz = {}
    if df_roles is not None and not df_roles.empty:
        for _, r in df_roles.iterrows():
            id_r = r["ID_Rol"]
            nombre_r = r["Nombre_Rol"]
            perms = db_rbac.obtener_permisos_rol(id_r)
            
            # Mapeamos doble para que el sistema responda si le pasan el ID o el Nombre
            matriz[id_r] = perms
            matriz[nombre_r] = perms
            matriz[str(id_r)] = perms
            if nombre_r:
                matriz[str(nombre_r).upper()] = perms
                matriz[str(nombre_r).lower()] = perms
                
    return matriz

def obtener_menu_por_rol(rol):
    """
    Retorna una lista de tuplas con los módulos permitidos para cualquier rol.
    """
    permisos = db_rbac.obtener_permisos_rol(rol)
    menu = []
    for clave_mod, etiqueta in MODULOS_SISTEMA.items():
        acciones = permisos.get(clave_mod, [])
        if "ver" in acciones:
            menu.append((etiqueta, clave_mod))
    return menu