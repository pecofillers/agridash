import streamlit as st
import pandas as pd
# Importaremos un nuevo lector de tablas maestras
from modulos.base_datos import guardar_produccion_semanal, obtener_ubicaciones_maestras

def modulo_operativo():
    st.markdown("<h2 class='main-title'>👨‍🌾 MÓDULO OPERATIVO - REGISTRO Y EDICIÓN</h2>", unsafe_allow_html=True)
    
    # 1. LEER DE LA BASE DE DATOS RELACIONAL (Ya no es una lista estática)
    df_ubicaciones = obtener_ubicaciones_maestras() 
    lista_bloques = df_ubicaciones['Bloque'].unique().tolist()
    
    col_sel1, col_sel2 = st.columns(2)
    bloque_seleccionado = col_sel1.selectbox("🏢 SELECCIONA EL BLOQUE", lista_bloques)
    
    # Filtramos las naves dinámicamente según el bloque seleccionado
    naves_disponibles = df_ubicaciones[df_ubicaciones['Bloque'] == bloque_seleccionado]['Nave'].unique().tolist()
    nave_seleccionada = col_sel2.selectbox("⛺ SELECCIONA LA NAVE", naves_disponibles)

    # ... (El resto de tu formulario de LUNES a DOMINGO se queda IGUAL) ...