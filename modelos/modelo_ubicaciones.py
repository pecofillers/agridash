import pandas as pd
import streamlit as st
from modelos.db_core import DBCore

class ModeloUbicaciones(DBCore):
    def __init__(self):
        super().__init__()

    def leer(self):
        """Retorna todas las ubicaciones para la tabla de administración."""
        query = "SELECT * FROM dim_ubicaciones ORDER BY Bloque, Nave, Cama"
        df = self.leer_datos(query)
        return df if not df.empty else pd.DataFrame(columns=["ID_Ubicacion", "Bloque", "Nave", "Cama", "Estado"])

    def obtener_todas(self):
        """Alias exacto de leer() para compatibilidad con administracion_ubicaciones.py"""
        return self.leer()

    def listar_bloques(self):
        """Retorna únicamente los bloques que tienen ubicaciones activas en la BD."""
        query = """
            SELECT DISTINCT Bloque 
            FROM dim_ubicaciones 
            WHERE Estado = 'ACTIVA' 
            ORDER BY Bloque
        """
        df = self.leer_datos(query)
        if df is not None and not df.empty and "Bloque" in df.columns:
            return df["Bloque"].tolist()
        return []

    def listar_naves(self, bloque):
        """Retorna únicamente las naves asociadas a un bloque existente y activo."""
        query = """
            SELECT DISTINCT Nave 
            FROM dim_ubicaciones 
            WHERE Bloque = :bloque AND Estado = 'ACTIVA' 
            ORDER BY Nave
        """
        df = self.leer_datos(query, {"bloque": bloque})
        if df is not None and not df.empty and "Nave" in df.columns:
            return df["Nave"].tolist()
        return []

    def listar_camas(self, bloque, nave):
        """Retorna únicamente las camas asociadas al bloque y nave específicos."""
        query = """
            SELECT DISTINCT Cama 
            FROM dim_ubicaciones 
            WHERE Bloque = :bloque AND Nave = :nave AND Estado = 'ACTIVA' 
            ORDER BY Cama
        """
        df = self.leer_datos(query, {"bloque": bloque, "nave": nave})
        if df is not None and not df.empty and "Cama" in df.columns:
            return df["Cama"].tolist()
        return []

    def crear_ubicaciones_lote(self, bloque, nave, cama_inicio, cama_fin):
        """
        Crea un rango de camas en lote (ej. de Cama 1 a Cama 10) 
        para un bloque y nave específicos, evitando duplicados.
        """
        try:
            creadas = 0
            bloque = bloque.strip().upper()
            nave = nave.strip().upper()

            for i in range(int(cama_inicio), int(cama_fin) + 1):
                cama_nombre = f"CAMA {i}"
                
                query_check = """
                    SELECT ID_Ubicacion FROM dim_ubicaciones 
                    WHERE Bloque = :b AND Nave = :n AND Cama = :c
                """
                df_existe = self.leer_datos(query_check, {"b": bloque, "n": nave, "c": cama_nombre})
                
                if df_existe.empty:
                    query_insert = """
                        INSERT INTO dim_ubicaciones (Bloque, Nave, Cama, Estado) 
                        VALUES (:b, :n, :c, 'ACTIVA')
                    """
                    self.ejecutar_accion(query_insert, {"b": bloque, "n": nave, "c": cama_nombre})
                    creadas += 1

            st.cache_data.clear()
            return True, f"✅ Se crearon exitosamente {creadas} camas nuevas en lote."
        except Exception as e:
            return False, f"⚠️ Error al crear lote de ubicaciones: {e}"

    def actualizar_todo(self, df_ubicaciones):
        """Permite guardar cambios masivos desde la vista de administración."""
        try:
            for _, row in df_ubicaciones.iterrows():
                query = """
                    UPDATE dim_ubicaciones 
                    SET Estado = :estado 
                    WHERE ID_Ubicacion = :id_ubn
                """
                self.ejecutar_accion(query, {
                    "estado": row.get("Estado"),
                    "id_ubn": row.get("ID_Ubicacion")
                })
            st.cache_data.clear()
            return True, "✅ Ubicaciones actualizadas correctamente."
        except Exception as e:
            return False, f"⚠️ Error al actualizar ubicaciones: {e}"

# Instancia global requerida por administracion_ubicaciones.py
tabla_ubicaciones = ModeloUbicaciones()

# Funciones helper exportadas para compatibilidad directa con las vistas
def listar_bloques():
    return tabla_ubicaciones.listar_bloques()

def listar_naves(bloque):
    return tabla_ubicaciones.listar_naves(bloque)

def listar_camas(bloque, nave):
    return tabla_ubicaciones.listar_camas(bloque, nave)