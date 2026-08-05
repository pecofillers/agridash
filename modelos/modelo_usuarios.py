import pandas as pd
import streamlit as st
from modelos.db_core import DBCore

class ModeloUsuarios(DBCore):
    def __init__(self):
        super().__init__()

    def leer(self):
        """Retorna todos los usuarios incluyendo el Nombre_Rol de dim_roles."""
        query = """
            SELECT u.ID_Usuario, u.Username, u.Nombre, u.Apellidos, u.Telefono, u.Correo, 
                   u.ID_Rol, r.Nombre_Rol, u.Estado, u.Intentos_Fallidos, u.Bloqueado_Hasta
            FROM dim_usuarios u
            LEFT JOIN dim_roles r ON u.ID_Rol = r.ID_Rol
            ORDER BY u.Nombre
        """
        return self.leer_datos(query)

    def obtener_usuario(self, username: str):
        """Obtiene un usuario por su username trayendo el nombre textual del rol."""
        query = """
            SELECT u.*, r.Nombre_Rol 
            FROM dim_usuarios u
            LEFT JOIN dim_roles r ON u.ID_Rol = r.ID_Rol
            WHERE LOWER(u.Username) = LOWER(:usr)
            LIMIT 1
        """
        df = self.leer_datos(query, {"usr": username})
        if not df.empty:
            data = df.iloc[0].to_dict()
            # Mantenemos compatibilidad con el sistema asignando Nombre_Rol como ID_Rol para la sesión
            data["ID_Rol_Num"] = data.get("ID_Rol")
            data["ID_Rol"] = data.get("Nombre_Rol", "OPERARIO")
            return data
        return None

    def crear_usuario(self, username, nombre, apellidos, telefono, correo, password_hash, id_rol_num=1, estado="ACTIVO"):
        """Crea un nuevo usuario asignando el ID_Rol numérico."""
        query = """
            INSERT INTO dim_usuarios 
            (Username, Nombre, Apellidos, Telefono, Correo, Password_Hash, ID_Rol, Estado) 
            VALUES (:usr, :nom, :ape, :tel, :cor, :pass, :rol, :est)
        """
        params = {
            "usr": username.strip().lower(), "nom": nombre.strip(), "ape": apellidos.strip(),
            "tel": telefono.strip(), "cor": correo.strip(), "pass": password_hash,
            "rol": id_rol_num, "est": estado
        }
        exito, msj = self.ejecutar_accion(query, params)
        if exito: st.cache_data.clear()
        return exito, msj

    def actualizar_todo(self, df_usuarios):
        """Actualiza el estado, intentos o bloqueos de usuarios."""
        try:
            for _, row in df_usuarios.iterrows():
                query = """
                    UPDATE dim_usuarios 
                    SET Estado = :est, Intentos_Fallidos = :intentos, Bloqueado_Hasta = :bloqueo
                    WHERE Username = :usr
                """
                self.ejecutar_accion(query, {
                    "est": row.get("Estado"),
                    "intentos": row.get("Intentos_Fallidos", 0),
                    "bloqueo": row.get("Bloqueado_Hasta"),
                    "usr": row.get("Username")
                })
            st.cache_data.clear()
            return True, "✅ Usuarios actualizados."
        except Exception as e:
            return False, f"⚠️ Error al actualizar usuarios: {e}"

tabla_usuarios = ModeloUsuarios()

def obtener_usuario(username: str):
    return tabla_usuarios.obtener_usuario(username)