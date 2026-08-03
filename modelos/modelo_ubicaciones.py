import pandas as pd
import streamlit as st
from modelos.db_core import DBCore

class ModeloUbicaciones(DBCore):
    def __init__(self):
        super().__init__()
        self.tabla = "dim_ubicaciones"

    def obtener_todas(self):
        """Retorna el listado completo de ubicaciones físicas."""
        query = f"SELECT * FROM {self.tabla} ORDER BY Bloque, Nave, CAST(Cama AS UNSIGNED)"
        return self.leer_datos(query)

    def leer_bloques(self):
        query = f"SELECT DISTINCT Bloque FROM {self.tabla} WHERE Estado = 'ACTIVA' ORDER BY Bloque"
        return self.leer_datos(query)

    def leer_naves(self, bloque):
        query = f"SELECT DISTINCT Nave FROM {self.tabla} WHERE Bloque = :bloque AND Estado = 'ACTIVA' ORDER BY Nave"
        return self.leer_datos(query, {"bloque": bloque})
        
    def leer_camas(self, bloque, nave):
        query = f"SELECT DISTINCT Cama FROM {self.tabla} WHERE Bloque = :bloque AND Nave = :nave AND Estado = 'ACTIVA' ORDER BY CAST(Cama AS UNSIGNED), Cama"
        return self.leer_datos(query, {"bloque": bloque, "nave": nave})

    def crear_ubicaciones_lote(self, bloque, nave, cama_inicio, cama_fin):
        """Permite crear múltiples camas de un solo clic para un Bloque y Nave."""
        try:
            for c in range(cama_inicio, cama_fin + 1):
                cama_str = f"CAMA {c}" if not str(c).upper().startswith("CAMA") else str(c)
                query = f"""
                    INSERT INTO {self.tabla} (Bloque, Nave, Cama, Estado)
                    VALUES (:b, :n, :c, 'ACTIVA')
                    ON DUPLICATE KEY UPDATE Estado = 'ACTIVA'
                """
                self.ejecutar_accion(query, {"b": bloque.strip().upper(), "n": nave.strip().upper(), "c": cama_str})
            
            st.cache_data.clear()
            return True, f"✅ Se registraron exitosamente las camas de la {cama_inicio} a la {cama_fin} en {bloque} - {nave}."
        except Exception as e:
            return False, f"⚠️ Error registrando ubicaciones: {e}"

tabla_ubicaciones = ModeloUbicaciones()

def listar_bloques():
    df = tabla_ubicaciones.leer_bloques()
    if df.empty:
        return [f"BLOQUE {i}" for i in range(1, 14)]
    return df['Bloque'].tolist()

def listar_naves(bloque: str):
    df = tabla_ubicaciones.leer_naves(bloque)
    if df.empty:
        return [f"NAVE {i}" for i in range(1, 16)]
    return df['Nave'].tolist()

def listar_camas(bloque: str, nave: str):
    df = tabla_ubicaciones.leer_camas(bloque, nave)
    if df.empty:
        return [f"CAMA {i}" for i in range(1, 31)]
    return df['Cama'].tolist()