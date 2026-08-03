import streamlit as st
import pandas as pd
import datetime
from modelos.modelo_ubicaciones import listar_bloques, listar_naves, listar_camas
from modelos.modelo_agronomia import tabla_agronomia
from seguridad.auth import requiere_autenticacion, requiere_permiso

@requiere_autenticacion
@requiere_permiso("agronomia", "ver")
def render():
    st.markdown("<h2 class='main-title'>🌱 EXPEDIENTE Y GESTIÓN DE CAMAS</h2>", unsafe_allow_html=True)
    st.info("Consulta el historial completo de una cama o registra una nueva siembra.")

    tab_historial, tab_siembra, tab_variedades = st.tabs([
        "📖 Historial de Cama", 
        "🪴 Registrar Nueva Siembra", 
        "🌸 Catálogo de Variedades"
    ])

    # ----------------------------------------------------
    # TAB 1: HISTORIAL DE LA CAMA (EL EXPEDIENTE)
    # ----------------------------------------------------
    with tab_historial:
        st.subheader("Buscador de Camas")
        c1, c2, c3 = st.columns(3)
        b_sel = c1.selectbox("Bloque:", listar_bloques(), key="hist_b")
        n_sel = c2.selectbox("Nave:", listar_naves(b_sel), key="hist_n")
        c_sel = c3.selectbox("Cama:", listar_camas(b_sel, n_sel), key="hist_c")

        if st.button("🔍 VER HISTORIAL DE ESTA CAMA", type="primary"):
            df_historial = tabla_agronomia.obtener_historial_cama(b_sel, n_sel, c_sel)
            
            if df_historial is not None and not df_historial.empty:
                st.success(f"Expediente encontrado para: {b_sel} - {n_sel} - {c_sel}")
                
                # Mostrar datos de la siembra activa en grande
                activa = df_historial[df_historial['Estado_Cierre'] == 'ACTIVA ACTUALMENTE']
                if not activa.empty:
                    info = activa.iloc[0]
                    col_k1, col_k2, col_k3 = st.columns(3)
                    col_k1.metric("Variedad Actual", info['Variedad'])
                    col_k2.metric("Plantas Vivas", f"{info['Cantidad_Plantas']:,.0f}")
                    col_k3.metric("Densidad", f"{info['Plantas_x_Metro']:.1f} pt/m²")
                else:
                    st.warning("Esta cama actualmente está vacía (sin siembra activa).")

                st.write("**Historial completo de siembras y erradicaciones:**")
                st.dataframe(df_historial, use_container_width=True, hide_index=True)
            else:
                st.info("Esta cama es nueva y no tiene ningún historial de siembras registrado.")

    # ----------------------------------------------------
    # TAB 2: REGISTRAR SIEMBRA
    # ----------------------------------------------------
    with tab_siembra:
        st.subheader("Sembrar una Cama")
        df_var = tabla_agronomia.obtener_variedades()
        
        if df_var.empty:
            st.warning("⚠️ Primero debes registrar variedades en la pestaña 'Catálogo de Variedades'.")
        else:
            with st.form("form_siembra"):
                col1, col2, col3 = st.columns(3)
                bs = col1.selectbox("Bloque", listar_bloques(), key="siem_b")
                ns = col2.selectbox("Nave", listar_naves(bs), key="siem_n")
                cs = col3.selectbox("Cama a sembrar", listar_camas(bs, ns), key="siem_c")

                col4, col5 = st.columns(2)
                # Diccionario para obtener el ID de la variedad elegida
                dict_variedades = dict(zip(df_var['Nombre_Variedad'], df_var['ID_Variedad']))
                var_sel = col4.selectbox("Variedad a plantar", list(dict_variedades.keys()))
                fecha = col5.date_input("Fecha de Siembra", datetime.date.today())

                col6, col7 = st.columns(2)
                plantas = col6.number_input("Cantidad Total de Plantas", min_value=1, step=10)
                metros = col7.number_input("Metros Lineales de la Cama", min_value=1.0, step=1.0)

                if st.form_submit_button("🌱 GUARDAR SIEMBRA", type="primary"):
                    id_var = dict_variedades[var_sel]
                    exito, msj = tabla_agronomia.registrar_siembra(bs, ns, cs, id_var, fecha, plantas, metros)
                    if exito:
                        st.success("✅ Siembra registrada. Si había una variedad anterior, se cerró automáticamente su ciclo.")
                    else:
                        st.error(msj)

    # ----------------------------------------------------
    # TAB 3: CATÁLOGO DE VARIEDADES
    # ----------------------------------------------------
    with tab_variedades:
        with st.expander("➕ Agregar Nueva Variedad"):
            with st.form("form_variedad"):
                v_nom = st.text_input("Nombre de Variedad (Ej: Freedom)")
                v_col = st.text_input("Color principal")
                v_ciclo = st.number_input("Ciclo estimado en días (Siembra a primer corte)", min_value=1, value=90)
                
                if st.form_submit_button("Guardar Variedad") and v_nom:
                    exito, msj = tabla_agronomia.crear_variedad(v_nom, v_col, v_ciclo)
                    (st.success if exito else st.error)(msj)

        st.write("Variedades Registradas:")
        df_v = tabla_agronomia.obtener_variedades()
        if not df_v.empty: st.dataframe(df_v, use_container_width=True, hide_index=True)