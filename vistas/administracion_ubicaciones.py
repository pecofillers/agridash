import streamlit as st
import pandas as pd
from modelos.modelo_ubicaciones import tabla_ubicaciones, listar_bloques, listar_naves
from seguridad.auth import requiere_autenticacion, requiere_permiso

@requiere_autenticacion
@requiere_permiso("administracion_ubicaciones", "ver")
def render():
    st.markdown("<h2 class='main-title'>📍 GESTIÓN DE BLOQUES, NAVES Y CAMAS</h2>", unsafe_allow_html=True)
    st.info("Administra la infraestructura física de la finca. Aquí puedes agregar nuevos bloques, naves o rangos de camas.")

    tab_crear, tab_listado = st.tabs(["➕ Crear Nuevas Camas / Naves", "📋 Ver Estructura de la Finca"])

    with tab_crear:
        st.subheader("Crear Ubicaciones en Lote")
        st.write("Ingresa los datos para generar automáticamente las camas en tu base de datos:")

        with st.form("form_crear_ubicacion"):
            col1, col2 = st.columns(2)
            
            with col1:
                bloque_input = st.text_input("Nombre / Número del Bloque:", placeholder="Ej: BLOQUE 1").strip().upper()
                nave_input = st.text_input("Nombre / Número de la Nave:", placeholder="Ej: NAVE 1").strip().upper()
                
            with col2:
                cama_inicio = st.number_input("Cama Inicial #:", min_value=1, value=1, step=1)
                cama_fin = st.number_input("Cama Final #:", min_value=1, value=20, step=1)

            btn_crear = st.form_submit_button("🏗️ CREAR / ACTUALIZAR UBICACIONES", type="primary")

            if btn_crear:
                if not bloque_input or not nave_input:
                    st.error("⚠️ Debes especificar el Bloque y la Nave.")
                elif cama_fin < cama_inicio:
                    st.error("⚠️ La cama final no puede ser menor a la cama inicial.")
                else:
                    exito, msj = tabla_ubicaciones.crear_ubicaciones_lote(bloque_input, nave_input, cama_inicio, cama_fin)
                    if exito:
                        st.success(msj)
                        st.rerun()
                    else:
                        st.error(msj)

    with tab_listado:
        st.subheader("Estructura Registrada")
        df_todas = tabla_ubicaciones.obtener_todas()

        if df_todas is not None and not df_todas.empty:
            st.dataframe(
                df_todas[['ID_Ubicacion', 'Bloque', 'Nave', 'Cama', 'Estado', 'Fecha_Creacion']], 
                use_container_width=True
            )
        else:
            st.warning("No hay ubicaciones registradas en el sistema aún.")