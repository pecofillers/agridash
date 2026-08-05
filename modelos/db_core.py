import pandas as pd
import streamlit as st
from sqlalchemy import create_engine, text

class DBCore:
    def __init__(self):
        # 1. Leer las credenciales seguras de Streamlit
        host = st.secrets["tidb"]["host"]
        port = st.secrets["tidb"]["port"]
        user = st.secrets["tidb"]["username"]
        password = st.secrets["tidb"]["password"]
        database = st.secrets["tidb"]["database"]
        
        # 2. Armar la URL de conexión (Usamos PyMySQL)
        # El parámetro ssl_verify_cert=true es el equivalente automático al <CA_PATH>
        db_url = f"mysql+pymysql://{user}:{password}@{host}:{port}/{database}?ssl_verify_cert=true&ssl_verify_identity=true"
        
        # 3. Crear el motor de base de datos
        self.engine = create_engine(db_url)

    def leer_datos(self, query: str, params: dict = None):
        """Ejecuta un SELECT y retorna un DataFrame de Pandas."""
        try:
            with self.engine.connect() as conn:
                df = pd.read_sql(text(query), conn, params=params)
            return df
        except Exception as e:
            st.error(f"Error de lectura en Base de Datos: {e}")
            return pd.DataFrame()

    def ejecutar_accion(self, query: str, params: dict = None):
        """Ejecuta un INSERT, UPDATE o DELETE."""
        try:
            with self.engine.connect() as conn:
                conn.execute(text(query), params or {})
                conn.commit()
            return True, "Operación exitosa"
        except Exception as e:
            return False, f"Error en BD: {e}"