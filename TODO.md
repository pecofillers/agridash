# AgriDash — Migración a Laravel (Streamlit → Web)

## Objetivo
Convertir la aplicación AgriDash (originalmente Streamlit + Python) a un proyecto **Laravel + Blade** desplegable en **hosting con cPanel** (Hostinger).

## Estructura del proyecto Laravel
- **Ruta:** `agridash-web/`
- **Backend:** PHP 8.2 + Laravel 12
- **BD:** MySQL (mismas tablas del proyecto Python)
- **Frontend:** Blade + Bootstrap 5.3 (CDN) + Chart.js (CDN)

---

## ✅ Pasos completados (verificados)

### Base y arquitectura
- [x] Crear estructura Laravel base (`agridash-web/`)
- [x] Migraciones de todas las tablas (`dim_*`, `fact_*`)
- [x] Modelos Eloquent (11 modelos: Usuario, Rol, PermisoRol, Ubicacion, Variedad, Siembra, Grupo, Colaborador, Produccion, RendimientoLabor)
- [x] Configurar guard de autenticación → `Usuario` (tabla `dim_usuarios`)
- [x] Configurar `getAuthPassword()` en Usuario para usar `Password_Hash` (bcrypt)
- [x] `AuthController` usa `password_verify()` para hashes `$2b$` de Python

### Seguridad / RBAC
- [x] `app/Support/Rbac.php` (helper: menú por rol, submodulos visibles, cache)
- [x] Middleware `VerificarPermiso.php` (permiso por módulo/acción)
- [x] Middleware `VerificarSubmodulo.php` (alias `check.submodulo`)
- [x] Gates en `AppServiceProvider` (RBAC nativo de Laravel)
- [x] Registro de middleware en `bootstrap/app.php`
- [x] Modelo `PermisoRol` (tabla `dim_permisos_rol`, `$timestamps = false`)
- [x] Rate limit por IP en login (`RateLimiter` nativo de Laravel)
- [x] Bloqueo por intentos fallidos persistentes en BD (`Intentos_Fallidos`, `Bloqueado_Hasta`)
- [x] Validación de contraseña fuerte mediante `Password::min(8)->letters()->mixedCase()->numbers()->symbols()`
- [x] `UsuarioRequest.php` con validación nativa (username regex, email, teléfono, roles existentes)
- [x] Impedir que un usuario desactive su propia cuenta en `cambiarEstado`
- [x] **CAPTCHA**: NO es necesario. La seguridad nativa de Laravel (RateLimiter + bloqueo por intentos + CSRF) es suficiente. Se eliminó el bloque HTML residual del login.

### Controladores
- [x] `AuthController` (login, logout, rate limit, bloqueo)
- [x] `DashboardController`
- [x] `ProduccionController` (guardar, actualizar)
- [x] `RendimientoController` (registro labor, grupos, reporte con umbrales, reporte semanal)
- [x] `AgronomiaController` (historial, siembra, variedades)
- [x] `UsuariosController` (crear, cambiar estado)
- [x] `RolesController` (guardar/editar permisos por pestaña, borrar)
- [x] `UbicacionesController` (crear, listado)
- [x] `ConfiguracionController` (gestionar usuarios + cambiar/restablecer contraseñas)

### Rutas (`routes/web.php`)
- [x] Definir todas las rutas con middleware de permisos y alias `check.submodulo`
- [x] Rutas de configuración: `crear_usuario`, `cambiar_estado`, `desbloquear`, `cambiar_contrasena`, `restablecer_contrasena`

### Vistas Blade
- [x] Layout principal `layouts/app.blade.php` (sidebar Offcanvas móvil + sidebar fijo escritorio)
- [x] Partial `layouts/_sidebar.blade.php` (menú dinámico por rol)
- [x] Partial `layouts/_navbar.blade.php` (topbar móvil)
- [x] Login `auth/login.blade.php` (sin CAPTCHA)
- [x] Dashboard `dashboard.blade.php`
- [x] Producción `produccion/index.blade.php`
- [x] Rendimiento: `rendimiento/index.blade.php`, `reporte.blade.php` (con líneas de objetivo verde/naranja), `reporte_semanal.blade.php`, `grupos.blade.php`
- [x] Agronomía `agronomia/index.blade.php`
- [x] Gestión de usuarios `usuarios/index.blade.php`
- [x] Roles `roles/index.blade.php`
- [x] Ubicaciones `ubicaciones/index.blade.php`
- [x] Configuración `configuracion/index.blade.php` con 2 sub-tabs: **Gestión de Usuarios** (crear, listar, cambiar estado, desbloquear) y **Cambio de Contraseñas** (propia + restablecer de otro usuario solo admin)

### Estilos / UX
- [x] `public/css/agridash.css` reescrito (Bootstrap nativo, Nunito 0.875rem, app-shell, offcanvas móvil, modo oscuro)
- [x] Botones azul claro corregidos → verde de marca
- [x] Compatibilidad `.card-dashboard` / `.chart-card`

### Limpieza de BD (migraciones de saneamiento)
- [x] `2026_08_08_000001_drop_unused_laravel_tables` — elimina `users` y `password_reset_tokens`
- [x] `2026_08_08_000002_drop_permisos_acciones_columns` — elimina `Permiso_Crear/Editar/Eliminar`
- [x] `2026_08_08_000003_conceder_submodulos_admin` — concede todas las pestañas a ADMIN y SUPERADMIN
- [x] Verificado: `dim_permisos_rol` queda con `ID_Permiso, ID_Rol, Modulo, Submodulo, Permiso_Ver`
- [x] Verificado: ADMIN recupera los 8 módulos con todos sus submodulos

---

## 📋 Pendientes (por hacer)

### Dashboard / Visión Gerencial
- [ ] Migrar KPIs y gráficas de la "Visión Gerencial" del Python (`vista_gerencial`) — actualmente el `DashboardController` solo muestra conteos básicos (colaboradores, usuarios, grupos, ubicaciones).
- [ ] Añadir gráficas con Chart.js (producción por semana, rendimiento por colaborador, etc.) y tarjetas KPI.
- [ ] Aplicar filtro por rol en las estadísticas del dashboard (misma lógica de acceso por rol del Python).

### Producción — Verificación de edición
- [ ] Verificar que la vista `produccion/index.blade.php` expone la edición de registros conectada a la ruta `produccion.actualizar` y al método `actualizar()` del controlador.
- [ ] (Si falta) Implementar UI de edición de registros de producción (la lógica en el controlador ya existe).

### Datos / Seeders
- [ ] Seeder de roles por defecto (ADMIN, OPERARIO, AGRONOMO, SUPERADMIN) si no existen en `dim_roles`.
- [ ] Seeder/verificación de que ADMIN y SUPERADMIN tengan todas las pestañas concedidas.
- [ ] Reemplazar el `DatabaseSeeder` por defecto (usa `User`, tabla eliminada) por uno acorde a `dim_usuarios`.

### Limpieza de código base Laravel
- [ ] Eliminar o inutilizar el modelo `User.php` y la factoría `UserFactory` (tabla eliminada) para evitar confusiones.
- [ ] Revisar `welcome.blade.php` (no se usa) — opcional eliminarlo.
- [ ] Eliminar `_diagnostico_db.php`, `listar_tablas.php`, `describir_tablas.php`, `ver_env.php`, `configurar_env.php`, `_registrar_migraciones.php` (archivos de diagnóstico temporales) o moverlos a una carpeta fuera de producción.

### Despliegue en Hostinger
- [ ] Configurar `.env` con credenciales MySQL de Hostinger (y `AUTH_MODEL=App\Models\Usuario`)
- [ ] Subir por FTP/Git con `public_html` → `public/`
- [ ] Instalar dependencias (`composer install`) en cPanel
- [ ] Generar app key (`php artisan key:generate`)
- [ ] Ejecutar migraciones/seeders (`php artisan migrate --seed`)
- [ ] Configurar SSL, dominio y caché

---

## Notas técnicas
- El campo de contraseña en `dim_usuarios` es `Password_Hash` y usa **bcrypt**. Los hashes de Python son `$2b$`; Laravel los verifica con `password_verify()`.
- El RBAC se controla **solo por pestaña**: filas en `dim_permisos_rol` con `Submodulo NOT NULL` y `Permiso_Ver = 1`. No hay columnas de acción por módulo.
- Los roles se relacionan por `ID_Rol` (numérico) → `Nombre_Rol`.
- Gráficas Plotly del Python se replican con **Chart.js** (CDN) + plugin de anotaciones para líneas de objetivo.
- `Rbac::menuPorRol()` y `Rbac::submodulosVisibles()` tienen caché; tras cambios en la BD de permisos ejecutar `php artisan cache:clear` o `Rbac::limpiarCache()`.
- **CAPTCHA**: se descartó por decisión del usuario. La seguridad nativa de Laravel (RateLimiter, bloqueo por intentos, CSRF, validación de contraseña) es suficiente.
- El acceso a la configuración está protegido por el submodulo `configuracion.usuarios` para la gestión de usuarios y `configuracion.credenciales` para el cambio/restablecimiento de contraseñas.
- El `DatabaseSeeder` por defecto referencia a `User` (tabla eliminada) y debe reemplazarse.
