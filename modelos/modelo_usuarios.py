import pandas as pd
import streamlit as st
from modelos.db_core import DBCore

class ModeloUsuarios(DBCore):
    def __init__(self):
        super().__init__()
        self.tabla = "dim_usuarios"

    def leer(self):
        """Lee todos los usuarios de la base de datos MySQL."""
        query = f"SELECT * FROM {self.tabla}"
        df = self.leer_datos(query)
        return df if not df.empty else pd.DataFrame()

    def actualizar_todo(self, df_actualizado):
        """
        Puente de compatibilidad: Recibe el DataFrame editado desde la vista de configuración
        y actualiza la base de datos MySQL fila por fila.
        """
        try:
            for _, row in df_actualizado.iterrows():
                query = f"""
                    UPDATE {self.tabla}
                    SET Nombre=:nombre, Apellidos=:apellidos, Telefono=:tel, Correo=:correo,
                        Password_Hash=:pwd, ID_Rol=:rol, Estado=:estado,
                        Intentos_Fallidos=:intentos, Bloqueado_Hasta=:bloqueo
                    WHERE Username=:username
                """
                params = {
                    "nombre": row.get("Nombre"),
                    "apellidos": row.get("Apellidos"),
                    "tel": row.get("Telefono"),
                    "correo": row.get("Correo"),
                    "pwd": row.get("Password_Hash"),
                    "rol": row.get("ID_Rol"),
                    "estado": row.get("Estado", "ACTIVO"),
                    "intentos": int(row.get("Intentos_Fallidos", 0)),
                    "bloqueo": row.get("Bloqueado_Hasta"),
                    "username": row.get("Username")
                }
                # Ejecutamos el UPDATE para cada fila
                self.ejecutar_accion(query, params)
                
            st.cache_data.clear() # Limpia la caché para refrescar
            return True, "✅ Usuarios actualizados correctamente en MySQL."
        except Exception as e:
            return False, f"⚠️ Error al actualizar usuarios: {e}"

tabla_usuarios = ModeloUsuarios()

@st.cache_data(ttl=30, show_spinner=False)
def obtener_usuario(username: str):
    """Busca un usuario específico en MySQL."""
    if not username:
        return None
        
    query = "SELECT * FROM dim_usuarios WHERE Username = :username"
    df = tabla_usuarios.leer_datos(query, {"username": username.strip().lower()})

    if df is None or df.empty:
        return None

    return df.iloc[0].to_dict()

def crear_usuario(username, nombre, apellidos, telefono, correo, password_hash, id_rol="OPERARIO"):
    """Inserta de manera segura un nuevo usuario directamente en MySQL."""
    query = """
        INSERT INTO dim_usuarios
        (Username, Nombre, Apellidos, Telefono, Correo, Password_Hash, ID_Rol, Estado, Intentos_Fallidos)
        VALUES
        (:user, :nombre, :apellidos, :tel, :correo, :pwd, :rol, 'ACTIVO', 0)
    """
    params = {
        "user": username.strip().lower(),
        "nombre": nombre.strip(),
        "apellidos": apellidos.strip(),
        "tel": str(telefono).strip(),
        "correo": correo.strip().lower(),
        "pwd": password_hash,
        "rol": id_rol
    }
    
    exito, msj = tabla_usuarios.ejecutar_accion(query, params)
    if exito:
        st.cache_data.clear()
        return True, "✅ Usuario registrado con éxito en MySQL."
    else:
        return False, msj