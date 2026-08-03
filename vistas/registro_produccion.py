import streamlit as st
import pandas as pd
from seguridad.auth import requiere_autenticacion, requiere_permiso
from modelos.modelo_ubicaciones import listar_bloques, listar_naves, listar_camas
from controladores.controlador_produccion import registrar_produccion, obtener_produccion_nave, actualizar_produccion_nave

@requiere_autenticacion
@requiere_permiso("registro_produccion", "ver") # 👈 ESTA ES LA MAGIA
def render():
    st.markdown("<h2 class='main-title'>👨‍🌾 MÓDULO OPERATIVO</h2>", unsafe_allow_html=True)
    st.info("Los datos registrados aquí se guardan de forma centralizada y en tiempo real en Google Sheets.")
    
    # ----------------------------------------------------
    # SELECTORES DINÁMICOS (Leídos desde la base de datos)
    # ----------------------------------------------------
    col_sel1, col_sel2 = st.columns(2)
    
    lista_bloques = listar_bloques()
    bloque_seleccionado = col_sel1.selectbox("🏢 SELECCIONA EL BLOQUE", lista_bloques)
    
    lista_naves = listar_naves(bloque_seleccionado)
    nave_seleccionada = col_sel2.selectbox("⛺ SELECCIONA LA NAVE", lista_naves)
    
    tab_nuevo, tab_editar = st.tabs(["📝 INGRESAR NUEVO REGISTRO", "✏️ VER Y EDITAR REGISTROS"])
    
    # ----------------------------------------------------
    # TAB 1: FORMULARIO DE INGRESO
    # ----------------------------------------------------
    with tab_nuevo:
        with st.form("form_registro_semanal", clear_on_submit=False):
            st.subheader(f"AGREGANDO DATOS A: {bloque_seleccionado} - {nave_seleccionada}")
            col_a1, col_a2, col_a3 = st.columns(3)
            
            # Las camas se filtran dinámicamente según la nave seleccionada
            lista_camas = listar_camas(bloque_seleccionado, nave_seleccionada)
            cama = col_a1.selectbox("CAMA", lista_camas)
            
            semana = col_a2.number_input("SEMANA #", min_value=1, max_value=52, value=1, step=1)
            año = col_a3.selectbox("AÑO", [2026, 2025, 2024, 2023])
            
            st.write("---")
            st.write("**PRODUCCIÓN DIARIA Y BAJAS (SE SUMAN AL TOTAL):**")
            c_lun, c_mar, c_mie, c_jue, c_vie, c_sab, c_dom, c_baj = st.columns(8)
            
            lunes = c_lun.number_input("LUNES", min_value=0, value=0, step=1)
            martes = c_mar.number_input("MARTES", min_value=0, value=0, step=1)
            miercoles = c_mie.number_input("MIÉRCOLES", min_value=0, value=0, step=1)
            jueves = c_jue.number_input("JUEVES", min_value=0, value=0, step=1)
            viernes = c_vie.number_input("VIERNES", min_value=0, value=0, step=1)
            sabado = c_sab.number_input("SÁBADO", min_value=0, value=0, step=1)
            domingo = c_dom.number_input("DOMINGO", min_value=0, value=0, step=1)
            bajas = c_baj.number_input("⚠️ BAJAS", min_value=0, value=0, step=1)
            
            # FÓRMULA: SUMA DE LOS 7 DÍAS MÁS LAS BAJAS
            total_semana = lunes + martes + miercoles + jueves + viernes + sabado + domingo + bajas
            
            st.write("")
            st.markdown(f"### 🎯 TOTAL CORTADO + BAJAS: **{total_semana}**")
            
            guardar = st.form_submit_button("💾 GUARDAR EN LA NUBE", use_container_width=True, type="primary")
            
            if guardar:
                nuevo_registro = {
                    "Bloque": bloque_seleccionado,
                    "Nave": nave_seleccionada,
                    "Cama": cama, 
                    "Semana": semana, 
                    "Año": año,
                    "Lunes": lunes, "Martes": martes, "Miercoles": miercoles,
                    "Jueves": jueves, "Viernes": viernes, "Sabado": sabado,
                    "Domingo": domingo, "Bajas": bajas, 
                    "Total": total_semana
                }
                
                # Enviamos los datos al controlador
                try:
                    exito, mensaje = registrar_produccion(nuevo_registro)
                    if exito:
                        st.success(f"✅ ¡REGISTRO GUARDADO EN LA NUBE EXITOSAMENTE!")
                    else:
                        st.error(mensaje)
                except Exception as e:
                    st.error(f"Error técnico al guardar: {e}")

    # ----------------------------------------------------
    # TAB 2: EDITOR EN VIVO
    # ----------------------------------------------------
    with tab_editar:
        st.subheader(f"BASE DE DATOS: {bloque_seleccionado} - {nave_seleccionada}")
        
        # Pedimos los datos actuales al controlador (filtrados por nave y bloque)
        df_bd = obtener_produccion_nave(bloque_seleccionado, nave_seleccionada)
        
        if df_bd is not None and not df_bd.empty:
            st.info("Haz doble clic en las celdas para editar. Al terminar, presiona 'ACTUALIZAR NUBE'.")
            
            datos_editados = st.data_editor(df_bd, num_rows="dynamic", use_container_width=True)
            
            if st.button("🔄 ACTUALIZAR NUBE CON ESTOS CAMBIOS", type="primary"):
                # Recalculamos totales locales antes de enviar
                columnas_dias = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo', 'Bajas']
                cols_presentes = [c for c in columnas_dias if c in datos_editados.columns]
                if cols_presentes:
                    datos_editados['Total'] = datos_editados[cols_presentes].sum(axis=1)

                exito, mensaje = actualizar_produccion_nave(bloque_seleccionado, nave_seleccionada, datos_editados)
                if exito:
                    st.success("✅ ¡LOS DATOS SE HAN ACTUALIZADO EN GOOGLE SHEETS!")
                else:
                    st.error(mensaje)
        else:
            st.warning(f"Todavía no hay datos registrados para la {nave_seleccionada} en el {bloque_seleccionado}.")