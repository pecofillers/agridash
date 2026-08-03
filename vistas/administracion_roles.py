import streamlit as st
from modelos.modelo_roles import ModeloRoles
from seguridad.rbac_config import MODULOS_SISTEMA, cargar_matriz_permisos
from seguridad.auth import requiere_autenticacion, requiere_permiso

@requiere_autenticacion
@requiere_permiso("administracion_roles", "ver")
def vista_administracion_roles():
    st.markdown("<h2 class='main-title'>🛡️ GESTIÓN DINÁMICA DE ROLES Y PERMISOS</h2>", unsafe_allow_html=True)
    st.info("Crea nuevos roles y gestiona qué acciones puede realizar cada uno en cada módulo.")
    
    modelo_roles = ModeloRoles()
    df_roles = modelo_roles.obtener_roles_activos()
    lista_roles = df_roles["ID_Rol"].tolist() if not df_roles.empty else ["ADMIN", "OPERARIO", "AGRONOMO"]

    tab_editar, tab_crear = st.tabs(["✏️ EDITAR PERMISOS DE ROL", "➕ CREAR NUEVO ROL"])

    # ----------------------------------------------------
    # TAB 1: Matriz Interactiva de Permisos (Checkboxes)
    # ----------------------------------------------------
    with tab_editar:
        rol_sel = st.selectbox("Selecciona el Rol a configurar:", lista_roles)
        matriz_actual = cargar_matriz_permisos().get(rol_sel, {})

        st.subheader(f"Configurando matriz para: **{rol_sel}**")
        st.write("---")

        permisos_nuevos = {}
        
        # Encabezados de tabla en interfaz
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
            ver = col2.checkbox("Ver", value=("ver" in acciones_actuales), key=f"{rol_sel}_{clave_mod}_ver", label_visibility="collapsed")
            crear = col3.checkbox("Crear", value=("crear" in acciones_actuales), key=f"{rol_sel}_{clave_mod}_crear", label_visibility="collapsed")
            editar = col4.checkbox("Editar", value=("editar" in acciones_actuales), key=f"{rol_sel}_{clave_mod}_editar", label_visibility="collapsed")
            eliminar = col5.checkbox("Eliminar", value=("eliminar" in acciones_actuales), key=f"{rol_sel}_{clave_mod}_eliminar", label_visibility="collapsed")

            permisos_nuevos[clave_mod] = {
                "ver": ver,
                "crear": crear,
                "editar": editar,
                "eliminar": eliminar
            }

        st.write("---")
        if st.button("💾 GUARDAR CAMBIOS EN PERMISOS", type="primary", use_container_width=True):
            exito, msj = modelo_roles.guardar_rol_y_permisos(
                id_rol=rol_sel,
                nombre_rol=rol_sel,
                descripcion="Rol actualizado desde interfaz",
                permisos_dict=permisos_nuevos
            )
            if exito:
                st.cache_data.clear() # Limpia la caché para aplicar permisos al instante
                st.success("✅ ¡Matriz de permisos actualizada correctamente en la nube!")
                st.rerun()
            else:
                st.error(msj)

    # ----------------------------------------------------
    # TAB 2: Formulario para crear un nuevo rol
    # ----------------------------------------------------
    with tab_crear:
        with st.form("form_nuevo_rol"):
            st.subheader("Registrar nuevo rol en el sistema")
            nuevo_id = st.text_input("Identificador del Rol (ej. AUDITOR, SUPERVISOR):").upper().strip()
            nuevo_nombre = st.text_input("Nombre descriptivo:")
            nueva_desc = st.text_area("Descripción de responsabilidades:")

            btn_crear = st.form_submit_button("➕ CREAR ROL", type="primary")

            if btn_crear:
                if not nuevo_id:
                    st.error("El identificador del rol no puede estar vacío.")
                else:
                    # Permisos por defecto al crear (Solo lectura en producción)
                    permisos_defecto = {m: {"ver": True, "crear": False, "editar": False, "eliminar": False} for m in MODULOS_SISTEMA}
                    exito, msj = modelo_roles.guardar_rol_y_permisos(nuevo_id, nuevo_nombre, nueva_desc, permisos_defecto)
                    if exito:
                        st.cache_data.clear()
                        st.success(f"✅ Rol **{nuevo_id}** registrado. Ahora puedes configurar sus permisos en la pestaña anterior.")
                        st.rerun()
                    else:
                        st.error(msj)