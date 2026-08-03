import streamlit as st
import pandas as pd
from modelos.modelo_usuarios import tabla_usuarios, crear_usuario
from modelos.modelo_roles import ModeloRoles
from seguridad.auth import requiere_autenticacion, requiere_permiso, hash_password

@requiere_autenticacion
@requiere_permiso("gestion_usuarios", "ver")
def render():
    st.markdown("<h2 class='main-title'>👥 GESTIÓN DE USUARIOS DEL SISTEMA</h2>", unsafe_allow_html=True)
    st.info("Registra nuevas personas en el sistema y asígnales un rol para controlar a qué módulos pueden acceder.")

    tab_crear, tab_listar = st.tabs(["➕ Registrar Nuevo Usuario", "📋 Directorio de Usuarios"])
    modelo_roles = ModeloRoles()

    # ----------------------------------------------------
    # TAB 1: FORMULARIO DE REGISTRO
    # ----------------------------------------------------
    with tab_crear:
        with st.form("form_crear_usuario", clear_on_submit=True):
            st.subheader("Datos del Colaborador")
            c1, c2, c3 = st.columns(3)
            
            with c1:
                nuevo_nombre = st.text_input("Nombres:").strip()
                nuevos_apellidos = st.text_input("Apellidos:").strip()
                telefono = st.text_input("Teléfono / Celular:").strip()
                
            with c2:
                correo = st.text_input("Correo Electrónico:").strip().lower()
                nuevo_user = st.text_input("Username (Para Iniciar Sesión):", placeholder="Ej: jperez").strip().lower()
                
            with c3:
                # Obtenemos los roles directo de MySQL para que el selectbox sea dinámico
                df_roles = modelo_roles.obtener_roles_activos()
                roles_disponibles = df_roles["ID_Rol"].tolist() if not df_roles.empty else ["OPERARIO"]
                
                nuevo_rol = st.selectbox("Rol en el Sistema:", roles_disponibles)
                nueva_pass = st.text_input("Contraseña Temporal:", type="password")

            st.write("")
            btn_guardar_user = st.form_submit_button("💾 CREAR USUARIO", type="primary", use_container_width=True)
            
            if btn_guardar_user:
                if not nuevo_user or not nueva_pass or not nuevo_nombre:
                    st.error("⚠️ Los campos de Nombres, Username y Contraseña son obligatorios.")
                else:
                    # Enviamos los datos al modelo para que haga el INSERT en MySQL
                    try:
                        exito, msj = crear_usuario(
                            nuevo_user, nuevo_nombre, nuevos_apellidos, 
                            telefono, correo, hash_password(nueva_pass), id_rol=nuevo_rol
                        )
                        if exito:
                            st.success(f"✅ ¡Usuario **{nuevo_user}** ({nuevo_rol}) registrado exitosamente!")
                        else:
                            st.error(msj)
                    except Exception as e:
                        st.error(f"⚠️ Error técnico al crear usuario: {e}")

    # ----------------------------------------------------
    # TAB 2: DIRECTORIO / LISTADO
    # ----------------------------------------------------
    # ----------------------------------------------------
    # TAB 2: DIRECTORIO / LISTADO Y EDICIÓN
    # ----------------------------------------------------
    with tab_listar:
        st.subheader("Directorio de Accesos y Edición")
        df_usuarios = tabla_usuarios.leer()

        if df_usuarios is not None and not df_usuarios.empty:
            st.info("💡 **Doble clic** en cualquier celda para editar la información (El Username no se puede cambiar). Al finalizar, presiona el botón 'Guardar Cambios'.")
            
            # Filtramos las columnas que queremos mostrar/editar
            columnas_editar = ['Username', 'Nombre', 'Apellidos', 'Telefono', 'Correo', 'ID_Rol', 'Estado']
            df_mostrar = df_usuarios[columnas_editar].copy()
            
            # Obtenemos los roles para el menú desplegable de edición
            df_roles_edit = modelo_roles.obtener_roles_activos()
            lista_roles = df_roles_edit["ID_Rol"].tolist() if not df_roles_edit.empty else ["OPERARIO"]

            # Generamos el editor visual
            datos_editados = st.data_editor(
                df_mostrar,
                use_container_width=True,
                hide_index=True,
                disabled=["Username"], # Protegemos el username
                column_config={
                    "Estado": st.column_config.SelectboxColumn("Estado", options=["ACTIVO", "INACTIVO"]),
                    "ID_Rol": st.column_config.SelectboxColumn("Rol del Sistema", options=lista_roles)
                }
            )

            # Botón para consolidar los cambios en MySQL
            if st.button("🔄 GUARDAR CAMBIOS DE USUARIOS", type="primary"):
                # Mezclamos los datos editados con los originales (para no perder IDs ni contraseñas)
                df_final = df_usuarios.copy()
                df_final.update(datos_editados)
                
                # Enviamos al modelo para hacer los UPDATE en MySQL
                exito, msj = tabla_usuarios.actualizar_todo(df_final)
                if exito:
                    st.success("✅ ¡Base de datos de usuarios actualizada correctamente!")
                    st.rerun()
                else:
                    st.error(msj)
        else:
            st.warning("No se encontraron usuarios en la base de datos.")