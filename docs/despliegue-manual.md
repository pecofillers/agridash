# Agridash: diagnóstico y despliegue manual

Fecha de revisión: 2026-09-17.

Este documento prepara los cambios; no acredita que desarrollo y producción ya sean equivalentes. No se ha modificado la aplicación ni ejecutado SQL de escritura. El esquema objetivo descrito procede del código y de TODO.md, no de una exportación completa de desarrollo.

## Contrato de producción confirmado

- La sincronización se realiza por bloque. La masiva recorre bloques individualmente.
- Se conservan los registros con Total = 0.
- Vaciar una celda reconocida actualiza el valor a cero.
- Eliminar una columna, cama u hoja del Excel no elimina su histórico de MySQL.
- Las consultas usan MySQL sin volver a descargar el Excel.
- No se propone modificar procesarExcelDeBloque ni introducir borrado de ausentes.

## Evidencia y limitaciones

La conexión local respondió como MariaDB 10.4.32. SHOW TABLES respondió y fue posible leer cache y cache_locks. SHOW CREATE TABLE de dim_grupos falló; el intento siguiente recibió conexión rechazada. No se intentó arrancar, reparar ni modificar el servidor. El fallo por sí solo no permite diagnosticar corrupción.

Falta obtener la estructura completa de desarrollo y producción. Los comentarios y resultados copiados en TODO.md no sustituyen esta verificación.

## Ajustes de aplicación propuestos, todavía sin implementar

1. Mantener producción con el contrato anterior.
2. Integrar las altas y modificaciones de ubicaciones con ID_Bloque, conservando temporalmente Bloque para los módulos antiguos.
3. Resolver o crear el ciclo correcto al registrar/importar/editar siembras. Revisar los cambios de fecha y variedad para evitar que ID_Ciclo_Siembra quede asociado a otra combinación.
4. Histórico: validar una selección de un único ciclo; contar camas distintas; ordenar nave/cama según su formato real; agrupar producción por ID_Siembra una sola vez; conservar variedad y color y el denominador actual de plantas sembradas hasta acordar otro.
5. El histórico guarda ceros en la base, pero su presentación actual recorta el eje entre la primera y última semana positiva. Si todo es cero, no muestra columnas semanales. Propuesta: mostrar las semanas registradas del ciclo, incluidas las de cero, dentro de las fechas aplicables. No interpretar semanas futuras sin observar como producción cero ni eliminar registros para ajustar la pantalla.
6. Comparador: implementar ComparadorSiembrasController separado. Unificar ruta, vista y permiso con agronomia.comparador-siembras. Consultar producción por las siembras del ciclo, no todo el histórico de sus camas.
7. Propuesta pendiente de validar: semana de ciclo 1 = semana ISO que contiene Fecha_Siembra; siguientes semanas por fechas reales, sin multiplicar años por 52. Es compatible con producción semanal calendario; no representa intervalos diarios exactos desde el día de siembra.
8. Indicador: suma de producción semanal / suma de plantas sembradas del ciclo. No asumir que Bajas representa mortalidad de plantas. Diferenciar denominador cero de rendimiento cero.
9. Corregir rutas a métodos eliminados, comprobaciones de submódulo de operaciones y valores asc/desc de planos. No modificar estas rutas durante esta fase de diagnóstico.

## Inventario necesario en ambos entornos

Ejecutar database/manual/01_diagnostico_estructura.sql en la base seleccionada, mediante phpMyAdmin o una herramienta equivalente. Son consultas de lectura. Exportar además SOLO la estructura de todas las tablas, incluyendo índices, claves foráneas, vistas y disparadores si existen. No incluir contraseñas, .env, datos personales ni enlaces OneDrive en los resultados compartidos.

Comparar por definición, no únicamente por nombre: tipo y signo de cada columna, nulabilidad, valores predeterminados, auto_increment, columnas calculadas, índices, claves foráneas, motor y collation. Los IDs y las cantidades de registros no tienen que coincidir entre entornos.

## Cambios SQL que deben contrastarse

| Objeto | Contrato esperado por el código/notas | Cuidado al preparar el SQL definitivo |
| --- | --- | --- |
| dim_bloques | ID_Bloque, Codigo_Bloque único, Nombre_Bloque, Descripcion, url_onedrive, Estado, timestamps | Conservar los enlaces existentes y resolver códigos equivalentes antes de poblar |
| dim_ubicaciones | Campos anteriores, Metros_Lineales, Cuadros, ID_Bloque y relación con dim_bloques | No borrar Bloque: agronomía y planos aún lo usan; comprobar tipos compatibles de FK |
| dim_ciclos_siembras | Bloque, variedad, fecha, estado, timestamps; combinación bloque/variedad/fecha única según TODO | Comprobar si esa combinación identifica realmente todos los ciclos, incluyendo rebrotes |
| dim_siembras | ID_Ciclo_Siembra, fechas agronómicas, plantas, estado, Ciclo_Actual, deleted_at | No exigir NOT NULL hasta corregir todas las altas y verificar el relleno histórico |
| fact_produccion | ID_Ubicacion, ID_Siembra nullable, Anio, Semana, Bajas, Total, deleted_at | Conservar ceros, registros sin siembra e histórico ausente del Excel |
| Índice único de producción | (ID_Ubicacion, Anio, Semana) | Detectar duplicados antes de crearlo; no sumar ni borrar automáticamente |
| Índice de consulta de producción | (ID_Siembra, Anio, Semana) | Comprobar si ya existe uno equivalente |
| dim_permisos_rol | Submódulos historico_siembras, comparador-siembras y configuracion/bloques | Conceder por rol decidido; no abrir todos los permisos a todos los roles |
| planillas | Fuente anterior de configuración de enlaces | Copiar y verificar enlaces antes de retirar su uso; no ejecutar el DROP de TODO como primer paso |

El SQL definitivo debe basarse en la diferencia real de producción respecto a desarrollo. No ejecutar TODO.md entero: mezcla pasos intermedios, validaciones y operaciones destructivas, y no es un script repetible.

Revisar también las diferencias fuera de producción: el modelo Grupo usa Supervisor_Asignado mientras las migraciones crean ID_Supervisor; insumos usa columnas diferencia_* que difieren de las columnas consumo de su migración. Son ejemplos de por qué hay que comparar todas las tablas, no solo las nuevas.

## Orden de aplicación manual en producción

1. Obtener el inventario de ambos entornos y resolver primero la lectura de desarrollo. Registrar versión de PHP, MariaDB/MySQL y revisión desplegada.
2. Preparar el SQL específico y probarlo en una copia de la base de producción. Las migraciones históricas existentes no son una alternativa segura para ejecutar todo desde cero en el hosting.
3. Exportar respaldo de estructura y datos de producción y guardar copia de archivos/configuración del despliegue anterior. Verificar que el respaldo puede restaurarse en una copia.
4. Coordinar una ventana sin sincronizaciones ni edición de siembras/ubicaciones. phpMyAdmin y FTP no necesitan Artisan, pero sí un mecanismo efectivo para impedir escrituras durante la transición.
5. Aplicar primero cambios aditivos compatibles con el código anterior: tablas nuevas y columnas inicialmente nulas. No borrar campos usados por la versión desplegada.
6. Poblar bloques desde los códigos de producción. Resolver espacios, ceros iniciales y nombres equivalentes sin fusionarlos a ciegas. Enlazar ubicaciones por código; no copiar IDs de desarrollo.
7. Transferir enlaces desde la configuración anterior a dim_bloques, después de inspeccionar las columnas reales de planillas. Validar que cada bloque conserve su enlace. No sobrescribir valores nuevos con nulos.
8. Validar fechas, ubicaciones y variedades. Crear ciclos históricos y asociar siembras por las claves acordadas, sin copiar datos agrícolas de desarrollo sobre producción.
9. Validar referencias huérfanas, duplicados y nulabilidad. Crear restricciones/índices faltantes solo después de resolver resultados. No deduplicar producción automáticamente: un duplicado podría representar un dato legítimo o una importación repetida.
10. Desplegar el código probado, incluyendo archivos nuevos de modelos, controlador y vistas. No publicar el comparador mientras su controlador o rutas estén incompletos.
11. Verificar permisos por rol y configuración específica del hosting. Hacer pruebas de consulta y de altas/ediciones que deban crear o actualizar relaciones.
12. Aplicar restricciones más estrictas solo cuando código e histórico las satisfagan. Si se conserva planillas para reversión, documentar que está fuera de uso; su eliminación puede quedar para otra entrega.
13. Guardar fecha, revisión, SQL aplicado y resultados. Actualizar posteriormente las migraciones del repositorio para instalaciones nuevas sin volver a aplicar a ciegas las operaciones manuales. No marcar migraciones como ejecutadas sin verificar su equivalencia.

ALTER TABLE y otros DDL pueden confirmar cambios implícitamente. No presentar un único BEGIN/ROLLBACK como reversión de toda la actualización. Ante un fallo, detener el despliegue y evaluar el estado ya aplicado.

## Despliegue real del repositorio

.github/workflows/deploy.yml despliega por FTP cuando se hace push a main. No ejecuta Composer, migraciones, compilación frontend ni limpieza de cachés. Un commit local por sí solo no activa el workflow.

- El workflow contiene una contraseña FTP en texto plano. Rotarla y sustituirla por un GitHub Secret antes del siguiente despliegue; retirarla del archivo no revoca la credencial ya expuesta en su historial. No copiarla a esta guía.
- vendor y .env están excluidos. Si cambian dependencias, preparar vendor en un entorno compatible y subirlo por el procedimiento del hosting. Preservar las credenciales y APP_KEY de producción.
- Comprobar PHP 8.2 o superior y las extensiones requeridas por composer.lock, además de acceso HTTPS a OneDrive.
- Comprobar permisos de escritura de storage y bootstrap/cache, y el almacén de caché usado para el estado de sincronización.
- Antes de publicar, excluir del FTP notas SQL, documentación interna, scripts de diagnóstico y archivos temporales de trabajo. El workflow actual no excluye docs, TODO.md o database/manual. Revisar también que no suba cachés generadas localmente, logs o sesiones. No añadir endpoints públicos para ejecutar SQL o limpiar cachés.
- Si hay cachés de rutas/configuración/vistas en el hosting, usar el mecanismo autorizado de su panel. Cuando no exista Artisan, identificar primero los archivos generados concretos y respaldarlos antes de retirarlos por FTP; conservar directorios, .gitignore y datos de sesiones. No asumir que el FTP limpia cachés automáticamente.
- Confirmar que la raíz web y las reglas de acceso impiden descargar .env, SQL y otros archivos internos, especialmente porque el destino FTP es /htdocs/.

## Comprobación posterior

- Login y menú correctos para un rol autorizado y otro sin acceso.
- Bloques, naves y camas conservan sus relaciones y enlaces.
- Histórico: fecha, variedad/color, camas distintas, plantas, total y producción/planta coinciden con una consulta independiente del ciclo.
- Un ciclo con solo ceros conserva su información; las semanas intermedias en cero se muestran según la regla aprobada.
- Comparador separa ciclos de distintos años, conserva ceros y trata correctamente la transición ISO de 53 semanas.
- Registrar/importar/editar una siembra mantiene ID_Ciclo_Siembra coherente. Crear/editar una ubicación mantiene ID_Bloque y Bloque coherentes.
- Probar en la copia una celda positiva que pasa a vacía: actualiza a cero; quitar una semana completa conserva el histórico; repetir la sincronización no duplica registros. Validar que sincronizar un bloque no modifica otro.
- Sincronizar en producción solo tras validar los enlaces y confirmar que el archivo de ese bloque contiene la información esperada.

No se puede certificar equivalencia ni entregar un parche SQL final seguro hasta contar con ambos inventarios. Igualar esquemas y comportamiento no significa reemplazar los datos de producción por los de desarrollo.
