import pandas as pd
import streamlit as st
from modelos.modelo_produccion import tabla_produccion

def registrar_produccion(nuevo_registro: dict) -> tuple[bool, str]:
    """
    Recibe un diccionario desde la vista y lo añade como una nueva fila 
    en la tabla maestra 'fact_produccion' de Google Sheets.
    """
    try:
        df = tabla_produccion.leer()
        nuevo_df = pd.DataFrame([nuevo_registro])
        
        if df.empty:
            df_final = nuevo_df
        else:
            df_final = pd.concat([df, nuevo_df], ignore_index=True)
            
        exito, msj = tabla_produccion.actualizar_todo(df_final)
        
        if exito:
            st.cache_data.clear() # Refrescamos la caché para que el cambio se vea de inmediato
            
        return exito, msj
    except Exception as e:
        return False, f"Error interno al registrar: {e}"


def obtener_produccion_nave(bloque: str, nave: str) -> pd.DataFrame:
    """
    Descarga la base de datos completa pero retorna SOLO las filas 
    que corresponden al Bloque y Nave que el usuario quiere editar.
    """
    df = tabla_produccion.leer()
    
    if df.empty or 'Bloque' not in df.columns or 'Nave' not in df.columns:
        return pd.DataFrame()
        
    # Filtramos exactamente lo que el operario necesita ver
    filtrado = df[(df['Bloque'] == bloque) & (df['Nave'] == nave)]
    return filtrado


def actualizar_produccion_nave(bloque: str, nave: str, df_editado: pd.DataFrame) -> tuple[bool, str]:
    """
    Recibe los datos corregidos desde el editor de Streamlit (data_editor).
    Elimina los registros viejos de ESA nave y pega los nuevos, manteniendo
    intactos los registros del resto de la finca.
    """
    try:
        df = tabla_produccion.leer()
        if df.empty:
            return False, "La base de datos maestra está vacía."

        # 1. Encontramos y separamos los registros de los OTROS bloques y naves (lo que no se tocó)
        mask = (df['Bloque'] == bloque) & (df['Nave'] == nave)
        df_intacto = df[~mask]
        
        # 2. Unimos los registros intactos con el bloque de datos que el operario acaba de editar
        df_final = pd.concat([df_intacto, df_editado], ignore_index=True)
        
        # 3. Subimos todo de vuelta a Google Sheets
        exito, msj = tabla_produccion.actualizar_todo(df_final)
        
        if exito:
            st.cache_data.clear()
            
        return exito, msj
    except Exception as e:
        return False, f"Error al sincronizar con Google Sheets: {e}"