"""
seed_inicial.py
================
Ejecutar UNA sola vez (`python seed_inicial.py`) para:
  1. Crear el primer usuario ADMIN con contraseña hasheada en bcrypt.
  2. (Opcional) Migrar tus ubicaciones desde el REGISTRO_BLOQUES.xlsx legado
     hacia el nuevo dim_ubicaciones.xlsx normalizado.

No se ejecuta automáticamente al levantar la app: es una herramienta de
arranque, separada del código de producción.
"""

import getpass
import os
import sys

import pandas as pd

from modelos.modelo_usuarios import crear_usuario, tabla_usuarios
from modelos.modelo_ubicaciones import crear_ubicacion, tabla_ubicaciones
from seguridad.auth import hash_password


def crear_admin_inicial():
    if not tabla_usuarios.leer().empty:
        print("⚠️  dim_usuarios.xlsx ya tiene datos. Se omite la creación del ADMIN inicial.")
        return
    print("== Creación del primer usuario ADMIN ==")
    username = input("Username: ").strip().lower()
    nombre = input("Nombre completo: ").strip()
    password = getpass.getpass("Contraseña: ")
    crear_usuario(username, nombre, hash_password(password), id_rol="ADMIN")
    print(f"✅ Usuario ADMIN '{username}' creado.")


def migrar_ubicaciones_legado(ruta_registro_bloques: str):
    """
    Lee un archivo legado con hojas tipo 'Bloque 1' y columnas
    Nave/Cama/... (como tu REGISTRO_BLOQUES.xlsx) y crea cada combinación
    Bloque/Nave/Cama en dim_ubicaciones.xlsx si no existe todavía.
    """
    if not os.path.exists(ruta_registro_bloques):
        print(f"⚠️  No se encontró {ruta_registro_bloques}, se omite la migración.")
        return

    xls = pd.ExcelFile(ruta_registro_bloques)
    creadas, existentes = 0, 0
    for nombre_hoja in xls.sheet_names:  # p.ej. "Bloque 1"
        df = xls.parse(nombre_hoja)
        columnas = {c.strip().lower(): c for c in df.columns}
        if "nave" not in columnas or "cama" not in columnas:
            continue
        for _, fila in df.iterrows():
            nave, cama = fila.get(columnas["nave"]), fila.get(columnas["cama"])
            if pd.isna(nave) or pd.isna(cama):
                continue
            try:
                crear_ubicacion(bloque=nombre_hoja.upper(), nave=f"NAVE {int(nave)}", cama=int(cama))
                creadas += 1
            except ValueError:
                existentes += 1  # ya existía, no es un error
    print(f"✅ Migración completada: {creadas} ubicaciones nuevas, {existentes} ya existían.")


if __name__ == "__main__":
    crear_admin_inicial()
    if len(sys.argv) > 1:
        migrar_ubicaciones_legado(sys.argv[1])
    else:
        print("Tip: corre 'python seed_inicial.py /ruta/a/REGISTRO_BLOQUES.xlsx' para migrar ubicaciones legadas.")
