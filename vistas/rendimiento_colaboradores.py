import streamlit as st
import pandas as pd
import datetime
import plotly.express as px
import plotly.graph_objects as go

from modelos.modelo_rendimientos import tabla_rendimientos
from modelos.modelo_usuarios import tabla_usuarios
from seguridad.auth import requiere_autenticacion, requiere_permiso

@requiere_autenticacion
@requiere_permiso("rendimiento_colaboradores", "ver")
def render():
    usuario_actual = st.session_state.get("username", "admin")
    rol_actual = st.session_state.get("rol", "OPERARIO")

    st.markdown("<h2 class='main-title'>⏱️ RENDIMIENTO Y MANO DE OBRA</h2>", unsafe_allow_html=True)
    if rol_actual in ["ADMIN", "SUPERADMIN"]:
        st.info("👋 Modo Administrador: Control total sobre todos los grupos, supervisores e integrantes.")
    else:
        st.info(f"👋 Hola {usuario_actual}. Panel de gestión de rendimiento para tu grupo asignado.")

    tab_ingreso, tab_reporte, tab_equipo = st.tabs([
        "📝 Registro de Labor", 
        "📊 Reporte y Gráficas Avanzadas", 
        "👥 Gestión de Grupos y Equipo"
    ])

    # ----------------------------------------------------
    # TAB 1: REGISTRO DE LABOR
    # ----------------------------------------------------
    with tab_ingreso:
        st.subheader("Ingreso de Tiempos y Producción")
        
        df_colab = tabla_rendimientos.obtener_colaboradores(usuario_actual, rol_actual)
        
        if df_colab.empty:
            st.warning("⚠️ No hay colaboradores asignados a tu grupo. Ve a la pestaña 'Gestión de Grupos y Equipo' para agregarlos.")
        else:
            with st.form("form_rendimiento", clear_on_submit=False):
                c_f1, c_f2, c_f3 = st.columns([1, 2, 2])
                fecha = c_f1.date_input("📅 Fecha", datetime.date.today())
                
                dict_colab = dict(zip(df_colab['Nombre_Colaborador'], df_colab['ID_Colaborador']))
                dict_grupo = dict(zip(df_colab['Nombre_Colaborador'], df_colab['Nombre_Grupo']))
                
                nombre_sel = c_f2.selectbox("👤 Colaborador", list(dict_colab.keys()))
                id_colab_sel = dict_colab[nombre_sel]
                grupo_sel = dict_grupo[nombre_sel]

                tipo_labor = c_f3.selectbox("✂️ Labor", ["DESHOJE", "CORTE LIMONIUM", "CORTE STATICE", "OTRA"])
                unidad_medida = "CUADROS" if tipo_labor == "DESHOJE" else "TALLOS"

                st.write("---")
                c_h1, c_h2, c_h3 = st.columns(3)
                h_inicio = c_h1.time_input("⏰ Hora Inicio", datetime.time(6, 0))
                h_fin = c_h2.time_input("⏰ Hora Fin", datetime.time(14, 0))

                t_ini = datetime.datetime.combine(fecha, h_inicio)
                t_fin = datetime.datetime.combine(fecha, h_fin)
                horas_totales = max(0, (t_fin - t_ini).total_seconds() / 3600.0)

                cantidad = c_h3.number_input(f"📦 Cantidad de {unidad_medida}", min_value=0.0, step=50.0, value=0.0)
                rend_hora = (cantidad / horas_totales) if horas_totales > 0 else 0.0

                st.markdown(f"""
                <div style='background-color: var(--secondary-background-color); padding: 12px; border-radius: 8px; border-left: 5px solid #2e7d32;'>
                    <b>⏱️ Horas Trabajadas:</b> {horas_totales:.2f} hrs &nbsp;|&nbsp; 
                    <b>⚡ Rendimiento:</b> <span style='color: #2e7d32; font-size: 1.2rem; font-weight: bold;'>{rend_hora:.1f} {unidad_medida}/hora</span>
                </div>
                <br>
                """, unsafe_allow_html=True)

                if st.form_submit_button("💾 GUARDAR REGISTRO", type="primary", use_container_width=True):
                    if horas_totales <= 0 or cantidad <= 0:
                        st.error("⚠️ Verifica las horas y que la cantidad sea mayor a cero.")
                    else:
                        exito, msj = tabla_rendimientos.registrar_labor(
                            fecha, id_colab_sel, nombre_sel, grupo_sel, usuario_actual, 
                            tipo_labor, unidad_medida, h_inicio, h_fin, horas_totales, cantidad, rend_hora
                        )
                        (st.success if exito else st.error)(msj)

    # ----------------------------------------------------
    # TAB 2: REPORTE Y GRÁFICAS ESTILO EXCEL / POWERBI
    # ----------------------------------------------------
    with tab_reporte:
        st.subheader("📋 Consolidado de Rendimientos y Métricas")
        
        df_colab_filtro = tabla_rendimientos.obtener_colaboradores(usuario_actual, rol_actual)
        lista_personas = ["TODOS"] + (df_colab_filtro['Nombre_Colaborador'].unique().tolist() if not df_colab_filtro.empty else [])

        col_r1, col_r2, col_r3, col_r4 = st.columns([1, 1, 1, 1])
        filtro_tiempo = col_r1.selectbox("Período:", ["Hoy", "Esta Semana", "Este Mes", "Personalizado"])
        filtro_labor = col_r2.selectbox("Filtrar Labor:", ["TODAS", "DESHOJE", "CORTE LIMONIUM", "CORTE STATICE"])
        filtro_persona = col_r3.selectbox("Filtrar Persona:", lista_personas)
        meta_rendimiento = col_r4.number_input("🎯 Meta Rendimiento (u/hr):", min_value=1.0, value=250.0 if "CORTE" in filtro_labor else 15.0, step=10.0)

        hoy = datetime.date.today()
        if filtro_tiempo == "Hoy": f_ini, f_fin = hoy, hoy
        elif filtro_tiempo == "Esta Semana": f_ini, f_fin = hoy - datetime.timedelta(days=hoy.weekday()), hoy
        elif filtro_tiempo == "Este Mes": f_ini, f_fin = hoy.replace(day=1), hoy
        else:
            c_d1, c_d2 = st.columns(2)
            f_ini = c_d1.date_input("Desde", hoy - datetime.timedelta(days=30))
            f_fin = c_d2.date_input("Hasta", hoy)

        df_rep = tabla_rendimientos.obtener_reporte(
            f_ini, f_fin, usuario_actual, rol_actual, labor=filtro_labor, colaborador=filtro_persona
        )

        if not df_rep.empty:
            total_cant = df_rep['Cantidad'].sum()
            total_hrs = df_rep['Horas_Trabajadas'].sum()
            prom_rend = (total_cant / total_hrs) if total_hrs > 0 else 0.0

            m1, m2, m3, m4 = st.columns(4)
            m1.metric("Total Producido", f"{total_cant:,.0f}")
            m2.metric("Horas Invertidas", f"{total_hrs:,.1f} hrs")
            m3.metric("Rendimiento Promedio", f"{prom_rend:,.1f} u/hr")
            m4.metric("Cumplimiento vs Meta", f"{(prom_rend/meta_rendimiento)*100:.1f}%")

            st.write("---")

            # ----------------------------------------------------
            # VISUALIZACIÓN GRÁFICA AVANZADA (PLOTLY)
            # ----------------------------------------------------
            st.subheader("📈 Rendimiento de Colaboradores vs Meta (Línea Roja)")

            if filtro_persona == "TODOS":
                # Agrupamos por colaborador
                df_agg = df_rep.groupby("Nombre_Colaborador").agg(
                    Total_Cantidad=('Cantidad', 'sum'),
                    Total_Horas=('Horas_Trabajadas', 'sum'),
                    Rendimiento_Promedio=('Rendimiento_Hora', 'mean')
                ).reset_index()

                # Asignamos color condicional: Verde si cumple la meta, Rojo si está por debajo
                df_agg['Color'] = df_agg['Rendimiento_Promedio'].apply(lambda x: '#2e7d32' if x >= meta_rendimiento else '#d32f2f')

                # Gráfico de Barras interactivo con Plotly
                fig = go.Figure()

                # Barras de Rendimiento
                fig.add_trace(go.Bar(
                    x=df_agg['Nombre_Colaborador'],
                    y=df_agg['Rendimiento_Promedio'],
                    text=df_agg['Rendimiento_Promedio'].round(1),
                    textposition='outside',
                    marker_color=df_agg['Color'],
                    name='Rendimiento Promedio (u/hr)'
                ))

                # Línea de Meta / Benchmark
                fig.add_shape(
                    type="line",
                    x0=-0.5, x1=len(df_agg)-0.5,
                    y0=meta_rendimiento, y1=meta_rendimiento,
                    line=dict(color="Red", width=3, dash="dash"),
                )

                fig.add_annotation(
                    x=len(df_agg)-1, y=meta_rendimiento,
                    text=f"Meta ({meta_rendimiento} u/hr)",
                    showarrow=False, yshift=15,
                    font=dict(color="Red", size=12, family="Arial")
                )

                fig.update_layout(
                    title="<b>Promedio de Rendimiento (Unidades / Hora) por Colaborador</b>",
                    xaxis_title="Colaborador",
                    yaxis_title="Unidades / Hora",
                    template="plotly_white",
                    height=450
                )

                fig.update_yaxes(rangemode="tozero")

                st.plotly_chart(fig, use_container_width=True)

                # Gráfico secundario: Producción acumulada total
                fig_cant = px.bar(
                    df_agg, x='Nombre_Colaborador', y='Total_Cantidad',
                    text='Total_Cantidad', color='Total_Cantidad',
                    color_continuous_scale='Greens',
                    title="<b>Volumen Total Cortado / Deshojado (Acumulado)</b>"
                )
                fig_cant.update_traces(texttemplate='%{text:,.0f}', textposition='outside')
                fig_cant.update_layout(template="plotly_white", height=400)
                st.plotly_chart(fig_cant, use_container_width=True)

            else:
                # Análisis individual
                st.markdown(f"**Histórico Diario para: {filtro_persona}**")
                df_ind = df_rep.groupby("Fecha").agg(
                    Total_Cantidad=('Cantidad', 'sum'),
                    Rendimiento_Dia=('Rendimiento_Hora', 'mean')
                ).reset_index()

                fig_ind = go.Figure()
                fig_ind.add_trace(go.Scatter(
                    x=df_ind['Fecha'], y=df_ind['Rendimiento_Dia'],
                    mode='lines+markers+text',
                    text=df_ind['Rendimiento_Dia'].round(1),
                    textposition='top center',
                    line=dict(color='#1976d2', width=3),
                    name='Rendimiento/Hora'
                ))

                # Línea de Meta
                fig_ind.add_shape(
                    type="line",
                    x0=df_ind['Fecha'].min(), x1=df_ind['Fecha'].max(),
                    y0=meta_rendimiento, y1=meta_rendimiento,
                    line=dict(color="Red", width=2, dash="dash")
                )

                fig_ind.update_layout(
                    title=f"<b>Evolución del Rendimiento Diario de {filtro_persona}</b>",
                    xaxis_title="Fecha", yaxis_title="Rendimiento (u/hr)",
                    template="plotly_white", height=420
                )
                fig_ind.update_yaxes(rangemode="tozero")

                st.plotly_chart(fig_ind, use_container_width=True)

            st.write("---")
            st.subheader("📋 Detalle de Registros")
            st.dataframe(df_rep.drop(columns=['ID_Rendimiento', 'ID_Colaborador']), use_container_width=True, hide_index=True)

        else:
            st.info("No hay registros para los filtros seleccionados.")

    # ----------------------------------------------------
    # TAB 3: GESTIÓN DE GRUPOS Y EQUIPOS
    # ----------------------------------------------------
    with tab_equipo:
        st.subheader("🏢 Todos los Grupos del Sistema")
        
        df_todos_us = tabla_usuarios.leer()
        lista_supervisores = df_todos_us['Username'].tolist() if (df_todos_us is not None and not df_todos_us.empty) else ["admin"]

        df_grupos = tabla_rendimientos.obtener_grupos(usuario_actual, rol_actual)

        if rol_actual in ["ADMIN", "SUPERADMIN"]:
            with st.expander("➕ Crear Nuevo Grupo", expanded=False):
                with st.form("form_crear_grupo"):
                    col_g1, col_g2 = st.columns(2)
                    nuevo_grupo = col_g1.text_input("Nombre del Nuevo Grupo (Ej: GRUPO 4)")
                    sup_asignado = col_g2.selectbox("Asignar Supervisor:", lista_supervisores)
                    
                    if st.form_submit_button("Crear Grupo", type="primary"):
                        if nuevo_grupo.strip():
                            exito, msj = tabla_rendimientos.crear_grupo(nuevo_grupo, sup_asignado)
                            (st.success if exito else st.error)(msj)
                            st.rerun()
                        else:
                            st.error("⚠️ Ingrese un nombre para el grupo.")

        if not df_grupos.empty:
            if rol_actual in ["ADMIN", "SUPERADMIN"]:
                st.info("💡 Haz doble clic en la columna 'Supervisor Asignado' para reasignar el grupo a otro supervisor.")
                datos_grupos_editados = st.data_editor(
                    df_grupos,
                    use_container_width=True,
                    hide_index=True,
                    disabled=["Nombre_Grupo"],
                    column_config={
                        "Nombre_Grupo": "Grupo",
                        "Supervisor_Asignado": st.column_config.SelectboxColumn("Supervisor Asignado", options=lista_supervisores)
                    }
                )
                if st.button("🔄 GUARDAR CAMBIOS DE GRUPOS", type="primary"):
                    exito, msj = tabla_rendimientos.actualizar_grupos(datos_grupos_editados)
                    if exito:
                        st.success(msj)
                        st.rerun()
                    else:
                        st.error(msj)
            else:
                st.dataframe(df_grupos, use_container_width=True, hide_index=True)
        else:
            st.warning("No hay grupos registrados en el sistema.")

        st.markdown("---")

        st.subheader("👥 Integrantes por Grupo")

        col_add, col_list = st.columns([1, 2])

        with col_add:
            st.write("**Asignar Persona a un Grupo**")
            if not df_grupos.empty:
                with st.form("form_nuevo_colab", clear_on_submit=True):
                    if df_todos_us is not None and not df_todos_us.empty:
                        df_todos_us['Nombre_Completo'] = df_todos_us['Nombre'].fillna('') + " " + df_todos_us['Apellidos'].fillna('')
                        lista_usuarios_sistema = df_todos_us['Nombre_Completo'].str.strip().tolist()
                    else:
                        lista_usuarios_sistema = []

                    nuevo_nombre = st.selectbox("Selecciona Usuario:", lista_usuarios_sistema)
                    grupo_para_colab = st.selectbox("Asignar al Grupo:", df_grupos['Nombre_Grupo'].tolist())

                    if st.form_submit_button("➕ Agregar al Grupo", type="primary", use_container_width=True):
                        if nuevo_nombre:
                            exito, msj = tabla_rendimientos.crear_colaborador(nuevo_nombre, grupo_para_colab)
                            if exito:
                                st.success(f"✅ {nuevo_nombre} asignado a {grupo_para_colab}.")
                                st.rerun()
                            else:
                                st.error(msj)
                        else:
                            st.error("⚠️ No hay usuarios disponibles.")
            else:
                st.warning("Crea un grupo primero.")

        with col_list:
            st.write("**Integrantes Actuales:**")
            df_colabs = tabla_rendimientos.obtener_colaboradores(usuario_actual, rol_actual)

            if df_colabs is not None and not df_colabs.empty:
                for idx, row in df_colabs.iterrows():
                    c_info, c_action = st.columns([3, 1])
                    with c_info:
                        st.write(f"👤 **{row['Nombre_Colaborador']}** — `{row['Nombre_Grupo']}` *(Supervisor: {row.get('Supervisor_Asignado', 'N/A')})*")
                    with c_action:
                        if st.button("❌ Quitar", key=f"btn_del_colab_{row['ID_Colaborador']}"):
                            exito, msj = tabla_rendimientos.quitar_colaborador_de_grupo(row['ID_Colaborador'])
                            if exito:
                                st.success(f"Removido {row['Nombre_Colaborador']} del grupo.")
                                st.rerun()
                            else:
                                st.error(msj)
            else:
                st.info("No hay colaboradores asignados a ningún grupo actualmente.")