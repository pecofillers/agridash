import pandas as pd
import streamlit as st
from modelos.db_core import DBCore

class ModeloAgronomia(DBCore):
    def __init__(self):
        super().__init__()
        
    # --- GESTIÓN DE VARIEDADES ---
    def obtener_variedades(self):
        query = "SELECT * FROM dim_variedades ORDER BY Nombre_Variedad"
        df = self.leer_datos(query)
        return df if not df.empty else pd.DataFrame(columns=["ID_Variedad", "Nombre_Variedad"])

    def crear_variedad(self, nombre, color, ciclo_dias):
        query = "INSERT INTO dim_variedades (Nombre_Variedad, Color, Ciclo_Dias) VALUES (:nom, :col, :ciclo)"
        exito, msj = self.ejecutar_accion(query, {"nom": nombre.strip().upper(), "col": color, "ciclo": ciclo_dias})
        if exito: st.cache_data.clear()
        return exito, msj

    # --- HISTORIAL Y SIEMBRAS ---
    def registrar_siembra(self, bloque, nave, cama, id_variedad, fecha_siembra, plantas, metros):
        # Primero, "cerramos" cualquier siembra anterior en esa cama que siga activa
        query_cierre = """
            UPDATE dim_siembras 
            SET Fecha_Fin = CURRENT_DATE 
            WHERE Bloque = :b AND Nave = :n AND Cama = :c AND Fecha_Fin IS NULL
        """
        self.ejecutar_accion(query_cierre, {"b": bloque, "n": nave, "c": cama})

        # Registramos la nueva siembra
        query_nueva = """
            INSERT INTO dim_siembras (Bloque, Nave, Cama, ID_Variedad, Fecha_Siembra, Cantidad_Plantas, Metros_Lineales)
            VALUES (:b, :n, :c, :var, :f_siem, :plantas, :metros)
        """
        params = {
            "b": bloque, "n": nave, "c": cama, "var": id_variedad, 
            "f_siem": fecha_siembra, "plantas": plantas, "metros": metros
        }
        exito, msj = self.ejecutar_accion(query_nueva, params)
        if exito: st.cache_data.clear()
        return exito, msj

    def obtener_historial_cama(self, bloque, nave, cama):
        query = """
            SELECT 
                s.ID_Siembra,
                v.Nombre_Variedad AS Variedad,
                s.Fecha_Siembra,
                IFNULL(s.Fecha_Fin, 'ACTIVA ACTUALMENTE') AS Estado_Cierre,
                s.Cantidad_Plantas,
                s.Metros_Lineales,
                s.Densidad_Plantacion AS Plantas_x_Metro
            FROM dim_siembras s
            LEFT JOIN dim_variedades v ON s.ID_Variedad = v.ID_Variedad
            WHERE s.Bloque = :b AND s.Nave = :n AND s.Cama = :c
            ORDER BY s.Fecha_Siembra DESC
        """
        return self.leer_datos(query, {"b": bloque, "n": nave, "c": cama})

tabla_agronomia = ModeloAgronomia()