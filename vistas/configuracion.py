import streamlit as st
import pandas as pd
# 👇 CORRECCIÓN 1: Eliminamos 'crear_usuario' de la importación
from modelos.modelo_usuarios import tabla_usuarios
from modelos.modelo_roles import ModeloRoles
from seguridad.auth import requiere_autenticacion, requiere_permiso, hash_password
from seguridad.rbac_config import MODULOS_SISTEMA, cargar_matriz_permisos

@requiere_autenticacion
@requiere_permiso("configuracion", "ver")
def render():
    st.markdown("<h2 class='main-title'>⚙️ PANEL DE CONFIGURACIÓN Y ADMINISTRACIÓN</h2>", unsafe_allow_html=True)
    st.info("Administra usuarios, control de accesos (RBAC), credenciales y estados de cuentas del sistema.")

    tab_usuarios, tab_credenciales, tab_rbac = st.tabs([
        "👥 Gestión de Usuarios y Estados", 
        "🔑 Cambio de Contraseñas", 
        "🛡️ Matriz de Permisos (RBAC)"
    ])

    modelo_roles = ModeloRoles()
    
    # Obtenemos los roles para usarlos en todos los tabs
    df_roles = modelo_roles.obtener_roles_activos()
    if not df_roles.empty and "Nombre_Rol" in df_roles.columns:
        dict_roles = dict(zip(df_roles["Nombre_Rol"], df_roles["ID_Rol"]))
    else:
        dict_roles = {"ADMIN": 1, "OPERARIO": 2}

    # -----------------------------------------------------------------
    # TAB 1: GESTIÓN DE USUARIOS
    # -----------------------------------------------------------------
    with tab_usuarios:
        st.subheader("➕ Crear Nuevo Usuario")
        with st.form("form_crear_usuario_config", clear_on_submit=True):
            c1, c2 = st.columns(2)
            with c1:
                nuevo_user = st.text_input("Username (Nombre de usuario):").strip().lower()
                nuevo_nombre = st.text_input("Nombres:").strip()
                nuevos_apellidos = st.text_input("Apellidos:").strip()
                telefono = st.text_input("Teléfono / Celular:").strip()
            with c2:
                correo = st.text_input("Correo Electrónico (para recuperación):").strip().lower()
                nueva_pass = st.text_input("Contraseña inicial:", type="password")
                
                # Selector de rol ajustado a IDs numéricos
                nuevo_rol_nombre = st.selectbox("Rol asignado:", list(dict_roles.keys()))
                nuevo_rol_id = dict_roles[nuevo_rol_nombre]

            btn_guardar_user = st.form_submit_button("💾 REGISTRAR USUARIO", type="primary")
            if btn_guardar_user:
                if not nuevo_user or not nueva_pass or not nuevo_nombre or not correo:
                    st.error("⚠️ Los campos de Usuario, Nombre, Correo y Contraseña son obligatorios.")
                else:
                    try:
                        # 👇 CORRECCIÓN 2: Llamamos a tabla_usuarios.crear_usuario enviando el id_rol_num
                        exito, msj = tabla_usuarios.crear_usuario(
                            nuevo_user, nuevo_nombre, nuevos_apellidos, telefono, 
                            correo, hash_password(nueva_pass), id_rol_num=nuevo_rol_id
                        )
                        if exito:
                            st.success(f"✅ ¡Usuario **{nuevo_user}** registrado exitosamente!")
                            st.rerun()
                        else:
                            st.error(msj)
                    except Exception as e:
                        st.error(f"⚠️ Error al crear usuario: {e}")

        st.markdown("---")
        st.subheader("📋 Listado y Control de Usuarios (Bloquear / Inhabilitar)")
        df_usuarios = tabla_usuarios.leer()

        if df_usuarios is not None and not df_usuarios.empty:
            for idx, row in df_usuarios.iterrows():
                # Mostramos el Nombre_Rol si existe, sino usamos el ID
                rol_mostrar = row.get('Nombre_Rol', row.get('ID_Rol'))
                with st.expander(f"👤 {row.get('Username')} — {row.get('Nombre', '')} {row.get('Apellidos', '')} [Rol: {rol_mostrar}]"):
                    col_info, col_accion1, col_accion2 = st.columns([2, 1, 1])
                    
                    with col_info:
                        st.write(f"**Correo:** {row.get('Correo', 'No registrado')}")
                        st.write(f"**Teléfono:** {row.get('Telefono', 'No registrado')}")
                        st.write(f"**Estado actual:** {row.get('Estado', 'ACTIVO')}")
                        st.write(f"**Intentos fallidos:** {row.get('Intentos_Fallidos', 0)}")

                    with col_accion1:
                        nuevo_estado = st.selectbox(
                            "Cambiar Estado", 
                            ["ACTIVO", "INHABILITADO"], 
                            index=0 if row.get('Estado', 'ACTIVO') == 'ACTIVO' else 1,
                            key=f"est_{idx}"
                        )
                        if st.button("Actualizar Estado", key=f"btn_est_{idx}"):
                            df_usuarios.at[idx, 'Estado'] = nuevo_estado
                            tabla_usuarios.actualizar_todo(df_usuarios)
                            st.success(f"✅ Estado actualizado a {nuevo_estado}.")
                            st.rerun()

                    with col_accion2:
                        st.write("**Desbloquear cuenta**")
                        if st.button("🔓 Resetear Bloqueo", key=f"unlock_{idx}"):
                            df_usuarios.at[idx, 'Intentos_Fallidos'] = 0
                            df_usuarios.at[idx, 'Bloqueado_Hasta'] = None
                            tabla_usuarios.actualizar_todo(df_usuarios)
                            st.success(f"✅ Bloqueo retirado.")
                            st.rerun()

    # -----------------------------------------------------------------
    # TAB 2: CAMBIO DE CONTRASEÑAS
    # -----------------------------------------------------------------
    with tab_credenciales:
        st.subheader("🔑 Restablecer Contraseña de Usuario")
        df_users_pass = tabla_usuarios.leer()
        lista_usernames = df_users_pass["Username"].tolist() if df_users_pass is not None and not df_users_pass.empty else []

        with st.form("form_cambio_pass"):
            user_seleccionado = st.selectbox("Selecciona el usuario:", lista_usernames)
            nueva_pass_1 = st.text_input("Nueva contraseña:", type="password")
            nueva_pass_2 = st.text_input("Confirma la nueva contraseña:", type="password")
            
            btn_cambiar_pass = st.form_submit_button("🔒 ACTUALIZAR CONTRASEÑA", type="primary")

            if btn_cambiar_pass:
                if not nueva_pass_1 or (nueva_pass_1 != nueva_pass_2):
                    st.error("⚠️ Las contraseñas no coinciden o están vacías.")
                else:
                    idx_u = df_users_pass.index[df_users_pass["Username"] == user_seleccionado]
                    if not idx_u.empty:
                        i = idx_u[0]
                        df_users_pass.at[i, "Password_Hash"] = hash_password(nueva_pass_1)
                        df_users_pass.at[i, "Intentos_Fallidos"] = 0
                        df_users_pass.at[i, "Bloqueado_Hasta"] = None
                        
                        tabla_usuarios.actualizar_todo(df_users_pass)
                        st.success(f"✅ ¡Contraseña actualizada con éxito para **{user_seleccionado}**!")
                    else:
                        st.error("⚠️ No se encontró el usuario.")

    # -----------------------------------------------------------------
    # TAB 3: MATRIZ RBAC
    # -----------------------------------------------------------------
    with tab_rbac:
        st.subheader("🛡️ Configuración de Roles y Permisos (RBAC)")
        
        # Usamos los nombres visuales para el usuario, pero procesamos con los IDs
        rol_sel_nombre = st.selectbox("Selecciona el Rol a configurar:", list(dict_roles.keys()))
        rol_sel_id = dict_roles[rol_sel_nombre]
        
        matriz_actual = cargar_matriz_permisos().get(rol_sel_id, {})

        permisos_nuevos = {}
        c_mod, c_ver, c_crear, c_edit, c_elim = st.columns([3, 1, 1, 1, 1])
        c_mod.markdown("**MÓDULO**")
        c_ver.markdown("**VER**")
        c_crear.markdown("**CREAR**")
        c_edit.markdown("**EDITAR**")
        c_elim.markdown("**ELIMINAR**")

        for clave_mod, etiqueta in MODULOS_SISTEMA.items():
            acciones_actuales = matriz_actual.get(clave_mod, [])
            col1, col2, col3, col4, col5 = st.columns([3, 1, 1, 1, 1])

            col1.write(etiqueta)
            ver = col2.checkbox("Ver", value=("ver" in acciones_actuales), key=f"rbac_{rol_sel_id}_{clave_mod}_ver", label_visibility="collapsed")
            crear = col3.checkbox("Crear", value=("crear" in acciones_actuales), key=f"rbac_{rol_sel_id}_{clave_mod}_crear", label_visibility="collapsed")
            editar = col4.checkbox("Editar", value=("editar" in acciones_actuales), key=f"rbac_{rol_sel_id}_{clave_mod}_editar", label_visibility="collapsed")
            eliminar = col5.checkbox("Eliminar", value=("eliminar" in acciones_actuales), key=f"rbac_{rol_sel_id}_{clave_mod}_eliminar", label_visibility="collapsed")

            permisos_nuevos[clave_mod] = {"ver": ver, "crear": crear, "editar": editar, "eliminar": eliminar}

        if st.button("💾 GUARDAR CAMBIOS DE PERMISOS", type="primary", use_container_width=True):
            exito, msj = modelo_roles.guardar_rol_y_permisos(
                id_rol=rol_sel_id, nombre_rol=rol_sel_nombre, descripcion="Actualizado desde configuración", permisos_dict=permisos_nuevos
            )
            if exito:
                st.success("✅ ¡Matriz de permisos actualizada correctamente!")
                st.rerun()
            else:
                st.error(msj)