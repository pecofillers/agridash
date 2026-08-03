import pandas as pd
import streamlit as st
from modelos.db_core import DBCore

class ModeloRoles(DBCore):
    def __init__(self):
        super().__init__()
        self.tabla_roles = "dim_roles"
        self.tabla_permisos = "dim_permisos_rol"

    def obtener_roles_activos(self):
        """Devuelve un DataFrame con todos los roles registrados en MySQL."""
        query = f"SELECT * FROM {self.tabla_roles}"
        df = self.leer_datos(query)
        return df if not df.empty else pd.DataFrame(columns=["ID_Rol", "Descripcion"])

    def obtener_matriz_permisos(self):
        """
        Lee la tabla SQL de permisos y la transforma en el diccionario 
        que Streamlit usa para dibujar el menú dinámico.
        Retorna: { 'ADMIN': { 'registro_produccion': ['ver', 'crear', ...], ... } }
        """
        query = f"SELECT * FROM {self.tabla_permisos}"
        df_permisos = self.leer_datos(query)
        
        matriz = {}
        if not df_permisos.empty:
            for _, row in df_permisos.iterrows():
                rol = row['ID_Rol']
                modulo = row['Modulo']
                
                if rol not in matriz:
                    matriz[rol] = {}
                    
                acciones = []
                # En MySQL, los booleanos suelen ser 1 o 0 (True/False)
                if row.get('Permiso_Ver'): acciones.append("ver")
                if row.get('Permiso_Crear'): acciones.append("crear")
                if row.get('Permiso_Editar'): acciones.append("editar")
                if row.get('Permiso_Eliminar'): acciones.append("eliminar")
                
                matriz[rol][modulo] = acciones
                
        return matriz

    def guardar_rol_y_permisos(self, id_rol, nombre_rol, descripcion, permisos_dict):
        """
        Guarda o actualiza un rol y sus permisos en la base de datos MySQL.
        permisos_dict = {'modulo_x': {'ver': True, 'crear': False...}}
        """
        try:
            # 1. Asegurarnos de que el Rol exista en dim_roles (Usamos INSERT IGNORE o REPLACE según MySQL)
            query_rol = f"""
                INSERT INTO {self.tabla_roles} (ID_Rol, Descripcion) 
                VALUES (:id_rol, :desc)
                ON DUPLICATE KEY UPDATE Descripcion = :desc
            """
            self.ejecutar_accion(query_rol, {"id_rol": id_rol, "desc": descripcion})

            # 2. Borrar los permisos viejos de este rol para insertar los nuevos limpios
            query_delete = f"DELETE FROM {self.tabla_permisos} WHERE ID_Rol = :id_rol"
            self.ejecutar_accion(query_delete, {"id_rol": id_rol})

            # 3. Insertar los nuevos permisos iterando el diccionario
            for modulo, acciones in permisos_dict.items():
                # Si no tiene ningún permiso en este módulo, lo saltamos para ahorrar espacio
                if not any(acciones.values()):
                    continue
                    
                query_insert = f"""
                    INSERT INTO {self.tabla_permisos} 
                    (ID_Rol, Modulo, Permiso_Ver, Permiso_Crear, Permiso_Editar, Permiso_Eliminar)
                    VALUES (:rol, :mod, :ver, :crear, :editar, :eliminar)
                """
                params = {
                    "rol": id_rol,
                    "mod": modulo,
                    "ver": 1 if acciones.get("ver") else 0,
                    "crear": 1 if acciones.get("crear") else 0,
                    "editar": 1 if acciones.get("editar") else 0,
                    "eliminar": 1 if acciones.get("eliminar") else 0
                }
                self.ejecutar_accion(query_insert, params)

            st.cache_data.clear() # Limpiar caché para que el menú lateral se actualice de inmediato
            return True, f"✅ Permisos actualizados correctamente para el rol {id_rol} en MySQL."
            
        except Exception as e:
            return False, f"⚠️ Error al guardar permisos: {e}"