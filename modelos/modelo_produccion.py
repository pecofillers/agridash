import pandas as pd
import streamlit as st
from modelos.db_core import DBCore

class ModeloProduccion(DBCore):
    def __init__(self):
        super().__init__()
        self.tabla = "fact_produccion"

    def leer(self):
        """Devuelve toda la producción (Útil para reportería)"""
        query = f"SELECT * FROM {self.tabla}"
        return self.leer_datos(query)

    def actualizar_todo(self, df_actualizado):
        """
        Guarda o actualiza registros de producción en MySQL.
        Reemplaza la lógica vieja del Excel.
        """
        if df_actualizado is None or df_actualizado.empty:
            return False, "No hay datos para guardar."
            
        try:
            for _, row in df_actualizado.iterrows():
                # Si no tiene ID, es un INSERT nuevo
                if pd.isna(row.get('ID_Produccion')) or row.get('ID_Produccion') == 0:
                    query = f"""
                        INSERT INTO {self.tabla} 
                        (Bloque, Nave, Cama, Semana, Anio, Lunes, Martes, Miercoles, Jueves, Viernes, Sabado, Domingo, Bajas, Total)
                        VALUES (:b, :n, :c, :sem, :anio, :l, :m, :mi, :j, :v, :s, :d, :baj, :tot)
                    """
                # Si tiene ID, es un UPDATE desde el editor
                else:
                    query = f"""
                        UPDATE {self.tabla} SET
                        Lunes=:l, Martes=:m, Miercoles=:mi, Jueves=:j, Viernes=:v, 
                        Sabado=:s, Domingo=:d, Bajas=:baj, Total=:tot
                        WHERE ID_Produccion = :id
                    """
                
                params = {
                    "id": row.get('ID_Produccion'),
                    "b": row.get('Bloque'), "n": row.get('Nave'), "c": row.get('Cama'),
                    "sem": row.get('Semana'), "anio": row.get('Anio') if 'Anio' in row else row.get('Año'),
                    "l": row.get('Lunes', 0), "m": row.get('Martes', 0), "mi": row.get('Miercoles', 0),
                    "j": row.get('Jueves', 0), "v": row.get('Viernes', 0), "s": row.get('Sabado', 0),
                    "d": row.get('Domingo', 0), "baj": row.get('Bajas', 0), "tot": row.get('Total', 0)
                }
                self.ejecutar_accion(query, params)
                
            return True, "✅ Datos sincronizados con MySQL local."
        except Exception as e:
            return False, f"⚠️ Error escribiendo en MySQL: {e}"

tabla_produccion = ModeloProduccion()