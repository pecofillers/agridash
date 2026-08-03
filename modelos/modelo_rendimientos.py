import pandas as pd
import streamlit as st
from modelos.db_core import DBCore

class ModeloRendimientos(DBCore):
    def __init__(self):
        super().__init__()

    # --- GRUPOS ---
    def obtener_grupos(self, supervisor=None, rol="OPERARIO"):
        if rol in ["ADMIN", "SUPERADMIN"]:
            return self.leer_datos("SELECT * FROM dim_grupos ORDER BY Nombre_Grupo")
        else:
            return self.leer_datos("SELECT * FROM dim_grupos WHERE Supervisor_Asignado = :sup", {"sup": supervisor})

    def crear_grupo(self, nombre_grupo, supervisor):
        query = "INSERT INTO dim_grupos (Nombre_Grupo, Supervisor_Asignado) VALUES (:grp, :sup)"
        exito, msj = self.ejecutar_accion(query, {"grp": nombre_grupo.strip().upper(), "sup": supervisor.strip()})
        if exito: st.cache_data.clear()
        return exito, msj

    def actualizar_grupos(self, df_actualizado):
        try:
            for _, row in df_actualizado.iterrows():
                query = "UPDATE dim_grupos SET Supervisor_Asignado = :sup WHERE Nombre_Grupo = :grp"
                self.ejecutar_accion(query, {
                    "sup": row.get("Supervisor_Asignado"), 
                    "grp": row.get("Nombre_Grupo")
                })
            st.cache_data.clear()
            return True, "✅ Grupos actualizados correctamente."
        except Exception as e:
            return False, f"⚠️ Error al actualizar grupos: {e}"

    # --- COLABORADORES ---
    def obtener_colaboradores(self, supervisor=None, rol="OPERARIO"):
        if rol in ["ADMIN", "SUPERADMIN"]:
            query = """
                SELECT c.ID_Colaborador, c.Nombre_Colaborador, c.Nombre_Grupo, g.Supervisor_Asignado, c.Estado 
                FROM dim_colaboradores c
                LEFT JOIN dim_grupos g ON c.Nombre_Grupo = g.Nombre_Grupo
                WHERE c.Estado = 'ACTIVO' AND c.Nombre_Grupo IS NOT NULL
                ORDER BY c.Nombre_Grupo, c.Nombre_Colaborador
            """
            return self.leer_datos(query)
        else:
            query = """
                SELECT c.ID_Colaborador, c.Nombre_Colaborador, c.Nombre_Grupo, g.Supervisor_Asignado, c.Estado 
                FROM dim_colaboradores c
                JOIN dim_grupos g ON c.Nombre_Grupo = g.Nombre_Grupo
                WHERE g.Supervisor_Asignado = :sup AND c.Estado = 'ACTIVO' AND c.Nombre_Grupo IS NOT NULL
                ORDER BY c.Nombre_Colaborador
            """
            return self.leer_datos(query, {"sup": supervisor})

    def crear_colaborador(self, nombre, grupo):
        query = "INSERT INTO dim_colaboradores (Nombre_Colaborador, Nombre_Grupo) VALUES (:nom, :grp)"
        exito, msj = self.ejecutar_accion(query, {"nom": nombre.strip(), "grp": grupo})
        if exito: st.cache_data.clear()
        return exito, msj

    def quitar_colaborador_de_grupo(self, id_colaborador):
        """Desasigna el grupo de un colaborador."""
        query = "UPDATE dim_colaboradores SET Nombre_Grupo = NULL WHERE ID_Colaborador = :id"
        exito, msj = self.ejecutar_accion(query, {"id": id_colaborador})
        if exito: st.cache_data.clear()
        return exito, msj

    # --- RENDIMIENTOS ---
    def registrar_labor(self, fecha, id_colab, nombre_colab, grupo, supervisor, labor, unidad, hora_inicio, hora_fin, horas_trabajadas, cantidad, rendimiento_hora):
        query = """
            INSERT INTO fact_rendimientos_labor 
            (Fecha, ID_Colaborador, Nombre_Colaborador, Nombre_Grupo, Supervisor, Tipo_Labor, Unidad_Medida, Hora_Inicio, Hora_Fin, Horas_Trabajadas, Cantidad, Rendimiento_Hora)
            VALUES (:f, :id_c, :nom_c, :grp, :sup, :lab, :uni, :h_ini, :h_fin, :h_trab, :cant, :rend)
        """
        params = {
            "f": fecha, "id_c": id_colab, "nom_c": nombre_colab, "grp": grupo, "sup": supervisor,
            "lab": labor, "uni": unidad, "h_ini": hora_inicio, "h_fin": hora_fin,
            "h_trab": horas_trabajadas, "cant": cantidad, "rend": rendimiento_hora
        }
        exito, msj = self.ejecutar_accion(query, params)
        if exito: st.cache_data.clear()
        return exito, msj

    # 👇 ESTA ES LA FUNCIÓN QUE CAUSABA EL ERROR (AHORA TIENE TODOS LOS PARÁMETROS)
    def obtener_reporte(self, f_ini, f_fin, supervisor, rol, labor="TODAS", colaborador="TODOS"):
        query = "SELECT * FROM fact_rendimientos_labor WHERE Fecha BETWEEN :f_ini AND :f_fin"
        params = {"f_ini": f_ini, "f_fin": f_fin}

        if rol not in ["ADMIN", "SUPERADMIN"]:
            query += " AND Supervisor = :sup"
            params["sup"] = supervisor

        if labor != "TODAS":
            query += " AND Tipo_Labor = :lab"
            params["lab"] = labor

        if colaborador != "TODOS":
            query += " AND Nombre_Colaborador = :colab"
            params["colab"] = colaborador

        query += " ORDER BY Fecha DESC, Hora_Inicio DESC"
        return self.leer_datos(query, params)

tabla_rendimientos = ModeloRendimientos()