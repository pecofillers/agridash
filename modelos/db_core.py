import pandas as pd
import streamlit as st
from sqlalchemy import create_engine, text

class DBCore:
    def __init__(self):
        try:
            # 1. Leer las credenciales seguras de Streamlit
            tidb_secrets = st.secrets.get("tidb", {})
            host = tidb_secrets.get("host", "127.0.0.1")
            port = int(tidb_secrets.get("port", 3306))
            user = tidb_secrets.get("username", "root")
            password = tidb_secrets.get("password", "")
            database = tidb_secrets.get("database", "agridash_db")
            
            # 2. Configurar SSL dinámicamente:
            # Si es TiDB Cloud activa SSL; si es Local (127.0.0.1 / localhost) se conecta normal sin SSL
            connect_args = {}
            if "tidbcloud.com" in host.lower() or (host not in ["127.0.0.1", "localhost"]):
                connect_args = {"ssl": {"check_hostname": True}}

            # 3. Armar la URL de conexión limpia
            db_url = f"mysql+pymysql://{user}:{password}@{host}:{port}/{database}"

            # 4. Crear el motor de SQLAlchemy con validación automática de conexiones
            self.engine = create_engine(
                db_url,
                connect_args=connect_args,
                pool_pre_ping=True,  # Revisa que la base de datos responda antes de hacer consultas
                pool_recycle=3600
            )
        except Exception as e:
            st.error(f"⚠️ Error en la configuración del motor de Base de Datos: {e}")
            self.engine = None

    def leer_datos(self, query: str, params: dict = None):
        """Ejecuta un SELECT y retorna un DataFrame de Pandas."""
        if self.engine is None:
            st.error("❌ No hay conexión configurada con la base de datos.")
            return pd.DataFrame()
        try:
            with self.engine.connect() as conn:
                df = pd.read_sql(text(query), conn, params=params)
            return df
        except Exception as e:
            st.error(f"Error de lectura en Base de Datos: {e}")
            return pd.DataFrame()

    def ejecutar_accion(self, query: str, params: dict = None):
        """Ejecuta un INSERT, UPDATE o DELETE."""
        if self.engine is None:
            return False, "❌ No hay conexión configurada con la base de datos."
        try:
            with self.engine.connect() as conn:
                conn.execute(text(query), params or {})
                conn.commit()
            return True, "Operación exitosa"
        except Exception as e:
            return False, f"Error en BD: {e}"