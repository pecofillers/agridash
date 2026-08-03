import streamlit as st
import pandas as pd
from sqlalchemy import text

class DBCore:
    def __init__(self):
        """Inicializa la conexión local con MySQL usando SQLAlchemy"""
        self.conn = st.connection("mysql", type="sql")

    def leer_datos(self, query: str, params: dict = None):
        """
        Ejecuta un SELECT en MySQL y retorna un DataFrame de Pandas.
        Ideal para integrarse sin romper las Vistas.
        """
        try:
            df = self.conn.query(query, params=params, ttl=0)
            return df
        except Exception as e:
            st.error(f"⚠️ Error de lectura en Base de Datos: {e}")
            return pd.DataFrame()

    def ejecutar_accion(self, query: str, params: dict = None):
        """
        Ejecuta operaciones INSERT, UPDATE o DELETE de forma segura.
        """
        try:
            with self.conn.session as session:
                session.execute(text(query), params)
                session.commit()
            return True, "✅ Operación exitosa."
        except Exception as e:
            return False, f"⚠️ Error escribiendo en Base de Datos: {e}"