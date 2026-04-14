# Plan de Cierre - camping_2

## Estado del Sistema - Base de Datos ✅

### Fase 1: Seguridad y Catálogos Base (✅ COMPLETADA)
**Migraciones:** 5 | **Modelos:** 5 | **Seeders:** 1

| Tabla | Estado | Registros Seed | Descripción |
|-------|--------|-----------------|-------------|
| `roles` | ✅ Activa | 7 | Roles del sistema (Admin, Gerente Producción, Gerente Compras, Supervisor Almacén, Operador Producción, Control Calidad, Solo Lectura) |
| `permissions` | ✅ Activa | 56 | Permisos granulares (7 roles × 8 módulos) |
| `users` | ✅ Activa | 1 | Usuario admin (email: admin@logicamp.local, pass: admin123456) |
| `unidades_medida` | ✅ Activa | 12 | Unidades base (m, cm, mm, kg, g, L, mL, pz, dz, m², m³, rl) |
| `tipos_producto` | ✅ Activa | 6 | Tipos: Mochila, Carpa, Sleeping Bag, Accesorios, Equipo Cocina, Iluminación |

### Fase 2: Proveedores y Ubicaciones (✅ COMPLETADA)
**Migraciones:** 4 | **Modelos:** 4 | **Seeders:** 2

| Tabla | Estado | Registros Seed | Descripción |
|-------|--------|-----------------|-------------|
| `categorias_insumo` | ✅ Activa | 18 | 6 categorías principales + 12 subcategorías |
| `ubicaciones_almacen` | ✅ Activa | 8 | Ubicaciones físicas de almacén |
| `proveedores` | ✅ Activa | 6 | Proveedores iniciales |
| `contactos_proveedores` | ✅ Activa | 6 | Contactos principales |

**Total Base de Datos:** 9 tablas | 127+ registros insertados | Sistema de seguridad operativo

### Fase 4: Compras e Inventario (✅ COMPLETADA)
**Migraciones:** 5 | **Modelos:** 5 | **Seeders:** 2

| Tabla | Estado | Registros Seed | Descripción |
|-------|--------|-----------------|-------------|
| `insumos` | ✅ Activa | 6 | Catálogo: Textiles Impermeables, Estructuras, Herrajes (INS-001 a INS-006) |
| `ordenes_compra` | ✅ Activa | 2 | Órdenes: OC-2026-001 (Pendiente), OC-2026-002 (Confirmada) |
| `ordenes_compra_detalles` | ✅ Activa | 4 | 4 líneas de detalle entre ambas órdenes |
| `lotes_insumos` | ✅ Activa | 0 | Estructura lista para recepción de lotes de proveedores (con calidad QA) |
| `movimientos_inventario` | ✅ Activa | 0 | Estructura para trazabilidad: Entrada, Salida, Consumo, Ajuste, Traspaso (13 tipos) |

**NUEVO TOTAL:** 14 tablas | 153+ registros | Módulo de compras operativo

#### Insumos Disponibles (Catalogo Phase 4):
- **INS-001:** Tela Ripstop 100D (150m stock)
- **INS-002:** Nylon 210D Naranja (80m stock)
- **INS-003:** Varilla Fibra Vidrio 7mm (250m stock)
- **INS-004:** Tubo Aluminio 16x16mm (120m stock)
- **INS-005:** Hebilla Regulable 25mm (2,000 pz stock)
- **INS-006:** D-Ring Metálico 20mm (1,200 pz stock)

#### Órdenes Activas:
- **OC-2026-001:** PROV-001 (TMT Textiles) - 300m total - Pendiente
- **OC-2026-002:** PROV-002 (Metales NE) - 370m total - Confirmada

---

## Enfoque de dominio (obligatorio)

Este proyecto queda orientado 100% a productos de acampar. Cualquier ejemplo puntual debe entenderse como referencia tecnica, no como limite funcional del negocio.

Lineamientos de dominio:

- El catalogo de terminados debe representar familias reales de camping (carpas, descanso, cocina outdoor, iluminacion, hidratacion y transporte).
- Las recetas de produccion (BOM) deben usar insumos acordes al rubro outdoor.
- Dashboard, reportes y trazabilidad deben mostrar operaciones del catalogo de camping, no un producto unico de ejemplo.
- Los datos de demostracion deben poblar varios productos de acampar para validar todo el flujo E2E.

---

## 0) Estado de cambios recientes (13-04-2026 en adelante)

### Cambios completados (limpieza de campos innecesarios)

| Campo Eliminado | Alcance | Motivo | Evidencia |
|---|---|---|---|
| `edificio`, `piso` (ubicaciones_almacen) | 7 archivos + 2 seeders | Campos no operativos; sin impacto en flujos de compras/producción/inventario | Migrations: 2026_04_13_000001_drop_edificio_piso_from_ubicaciones_almacen_table.php |
| `tipo_producto_id` (insumos) | 5 archivos + 1 seeder | Campo redundante (nullable); insumos se clasifican por categoría, no por tipo de producto terminado | Migrations: 2026_04_13_000002_drop_tipo_producto_from_insumos_table.php |

Archivos tocados durante limpieza:
- Controllers: InsumoController, UbicacionAlmacenController
- Requests: StoreInsumoRequest, UpdateInsumoRequest
- Models: Insumo, UbicacionAlmacen
- Views: insumos/create.blade.php, almacenes/create.blade.php, almacenes/edit.blade.php, almacenes/index.blade.php
- Seeders: InsumoSeeder, UbicacionAlmacenSeeder, LogisticaSeeder

✅ Todos los archivos modificados validados sin errores PHP.
✅ Todas las búsquedas grep confirman campos completamente removidos.

### Clarificaciones clave de producto

**Realizado 13-04-2026:**

1. **Dominio exclusivo de camping**: El sistema SOLO maneja mochilas, carpas, bolsas de dormir, accesorios y equipo outdoor. NO incluye equipo de cocina genérico, iluminación de interior, ni otros productos fuera del rubro.

2. **Seeders como artefacto de desarrollo**: Los datos de seed (ubicaciones, categorías, insumos, órdenes) son SOLO para:
   - Ambiente local de desarrollo (velocidad inicial).
   - Pruebas automatizadas (datos consistentes).
   - NO deben existir en producción real. La empresa ingresa sus propios datos vía UI.

3. **Decisión: Eliminar seeders no-camping**: Las entradas "Equipo Cocina" e "Iluminación" en TipoProductoSeeder deben eliminarse o marcarse como "sin usar" para mantener coherencia de dominio.

### Puntos pendientes identificados

**Pendiente 1: Implementar Master Catálogos (UI CRUD)** (Prioridad Alta)
- Ubicaciones de almacén (crear, editar, eliminar)
- Categorías de insumo (crear, editar, eliminar)
- Unidades de medida (crear, editar, eliminar)
- Tipos de producto (crear, editar, eliminar)
- Razón: Hoy dependen de seeders o carga manual. Producción necesita gestionar estos datos sin intervención de developer.

**Pendiente 2: Análisis de asignación warehouse-by-family** (Prioridad Media)
- Propuesta: Auto-asignar ubicación de almacén según familia de insumo (Textiles → Almacén A, Herrajes → Almacén B).
- Impacto identificado:
  - 🔴 Alto: StockBajoInsumosNotifier (línea 21), OrdenCompraController::procesarRecepcionOrden (línea 507).
  - 🟡 Medio: Movimientos de inventario, generación de órdenes automáticas.
  - 🟢 Bajo: Producción (consumo registrado a nivel de orden).
- Bloqueador: Requiere confirmación y refactoring en 2+ módulos. Pendiente decisión de negocio.

**Pendiente 3: Pruebas sanitarias de seeders** (Prioridad Media)
- Verificar que cada seeder contiene SOLO datos de dominio camping.
- Eliminar o comentar aliñas obsoletas (Equipo Cocina, Iluminación, etc.).
- Agregar comentarios explicativos en seeders sobre por qué cada entrada existe.

**Pendiente 4: Documentar flujos post-limpieza** (Prioridad Baja)
- Actualizar diagramas de flujo de insumos (ahora sin tipo_producto_id).
- Documentar impacto de campos eliminados en reportes/dashboards.

### Cómo estamos trabajando (contexto para futuros chats)

**Entorno**: VS Code Live Share (acceso remoto, sin terminal directo).

**Flujo de cambios**:
1. Identificar campo/funcionalidad con grep_search (busca exhaustiva).
2. Trazar dependencias: vistas → controllers → requests → models → seeders.
3. Aplicar cambios por capas (mostrar en vistas, validar en requests, permitir en models, limpiar seeders).
4. Crear migraciones para cambios de schema con lógica up/down segura.
5. Validar con get_errors (PHP linting) y grep_search aftermath (confirmar remoción total).

**Decisión de persistencia**: Cambios pequeños se aplican directamente. Cambios de arquitectura (ej. warehouse-by-family) requieren análisis de impacto antes de código.

**Alcance actual**: Mantenimiento (limpiar campos), refactoring (no implementar features nuevas aún). Objetivo es tener base sólida antes de próximas adiciones.

---

## 1) Meta de producto (Definition of Done)
La pagina se considera terminada cuando:

- Un usuario puede iniciar sesion y navegar por modulos sin errores.
- Produccion permite crear y gestionar ordenes de produccion con consumo de insumos.
- Terminados permite registrar stock final y movimientos basicos.
- Trazabilidad permite consultar un lote y ver su historial de etapas.
- Reportes y Dashboard muestran indicadores operativos con filtros por fecha.
- Pruebas criticas pasan en local antes de despliegue.

## 2) Cobertura actual del proyecto (31-03-2026)

Estado general de pruebas:

- ✅ 27 tests aprobados (116 assertions)

Cobertura funcional por modulo:

- ✅ Produccion: crear orden, cambiar estado, registrar consumo y validacion de stock (Feature tests de flujo + automatizacion por eventos).
- ✅ Terminados: alta de inventario terminado y ajustes basicos con pruebas de flujo.
- ✅ Trazabilidad: consulta de lote e historial en vista unificada con pruebas.
- ✅ Reportes: filtros operativos y exportacion CSV con pruebas.
- ✅ Dashboard: KPIs operativos y acceso por flujo autenticado con pruebas.
- ✅ Permisos y seguridad: middleware/permisos con pruebas especificas.
- ✅ Colas/Notificaciones (base): pruebas de hardening para destinatarios correctos e idempotencia diaria de alertas.
- ✅ Operacion y confiabilidad: comando `ops:health` para validar scheduler, cola, backlog y failed jobs.
- ✅ Calidad de datos (baseline): comando `data:quality:check` con validaciones de negativos, fechas inconsistentes y pendientes envejecidas.
- ✅ Resiliencia asincrona (baseline): pruebas unitarias de politicas de retry/backoff para jobs criticos.

## 3) Backlog restante enfocado en simulacion real

### A. Operacion y confiabilidad (Prioridad Alta)
Objetivo: que las simulaciones largas corran sin intervencion manual.

Estado actual (ya implementado):

- `composer dev` ya levanta `schedule:work` y worker de cola.
- Comando `php artisan ops:health` disponible para validacion operativa diaria.
- Script `composer sim:gate` agrega puerta de control (`ops:health --strict`, `data:quality:check --strict`, tests).

Tareas pendientes:

- Integrar alerta externa (correo/slack) cuando `ops:health --strict` falle.
- Definir umbrales por entorno para backlog de cola y ventana de jobs fallidos.
- Agregar procedimiento de recovery para `failed_jobs` (retry, requeue, descarte controlado).

Criterio de cierre:

- El scheduler corre de forma continua durante toda la simulacion.
- No quedan jobs fallidos sin atencion.

### B. Calidad de datos de simulacion (Prioridad Alta)
Objetivo: validar escenarios realistas del dominio camping E2E.

Estado actual (ya implementado):

- Comando `php artisan data:quality:check` disponible para validar integridad base.

Tareas pendientes:

- Poblar seeders con mas familias de terminados (carpas, descanso, cocina, iluminacion, hidratacion, transporte).
- Incluir ordenes con variacion de demanda (picos y baja rotacion).
- Simular faltantes de insumo, recepcion parcial y mermas para tensionar el flujo.

Criterio de cierre:

- El sistema soporta escenarios normales y de excepcion con datos representativos.

### C. Cobertura de pruebas de resiliencia (Prioridad Media)
Objetivo: reducir riesgo de regresiones en ejecucion asincrona.

Estado actual (ya implementado):

- Pruebas de hardening de notificaciones y idempotencia diaria.
- Pruebas unitarias de politicas de retry/backoff en jobs clave.

Tareas pendientes:

- Agregar pruebas de reintento y fallo transitorio en jobs criticos.
- Agregar prueba de notificaciones en ventanas de tiempo (evitar spam fuera de idempotencia).
- Agregar prueba de integracion de scheduler + cola para corrida continua.

Criterio de cierre:

- Se detectan automaticamente fallos de retry, duplicidad y degradacion de cola.

### D. UX y operacion diaria (Prioridad Media)
Objetivo: facilitar uso y soporte en ejecucion real.

Tareas pendientes:

- Homologar mensajes de error/exito entre modulos clave.
- Revisar ortografia/textos y consistencia de etiquetas en vistas.
- Confirmar paginacion y filtros en tablas de alto volumen.

Criterio de cierre:

- Operacion diaria fluida sin bloqueos ni ambiguedad en mensajes.

## 4) Orden de ejecucion recomendado (actualizado)

1. Operacion y confiabilidad (scheduler/cola/monitor)
2. Calidad de datos de simulacion
3. Pruebas de resiliencia asincrona
4. Pulido UX y cierre documental

## 5) Sprint sugerido (5 a 7 dias)

- Dia 1: Scheduler y monitoreo de cola
- Dia 2-3: Seeders y escenarios E2E de simulacion
- Dia 4-5: Pruebas de resiliencia y ajustes
- Dia 6-7: UX, checklist final y corrida de validacion

## 6) Checklist diario de avance (actualizado)

- Ejecutar simulacion corta (30-60 min) con scheduler y cola activos.
- Revisar jobs fallidos y notificaciones emitidas.
- Ejecutar pruebas automatizadas.
- Registrar hallazgos y accion correctiva del dia.

## 7) Checklist de pre-despliegue

- php artisan test
- php artisan migrate:status
- php artisan schedule:list
- Confirmar scheduler activo en entorno destino
- Verificar .env de produccion (APP_ENV, APP_DEBUG, DB, APP_URL)
- Verificar permisos de storage y bootstrap/cache
- Revisar logs sin errores criticos

## 8) Proximo paso inmediato (accion de hoy)

Ejecutar una simulacion controlada de 1 hora con estos criterios:

- Cola y scheduler activos todo el periodo.
- 1 flujo completo de produccion -> terminado -> trazabilidad.
- 1 escenario de stock bajo con notificacion.
- Cierre con evidencia: logs, jobs ejecutados y resultado de pruebas.

## 9) Cierre funcional puntual - Punto 16 (numeracion OC)

Estado: Implementado y definido funcionalmente.

Criterio funcional:

- Formato: `OC-YYYYMMDD-####`
- Secuencia diaria: incrementa de forma consecutiva por cada orden creada el mismo dia.
- Reinicio diario: al cambiar de fecha, la secuencia reinicia en `0001`.
- Unicidad: no debe repetirse `numero_orden`.

Evidencia tecnica:

- Implementacion en `app/Models/OrdenCompra.php` dentro de `generarNumeroOrden()`.
- Validacion automatizada agregada en `tests/Feature/OrdenCompraNumeroOrdenTest.php`.

## 10) Tabla de implementacion pendiente (UI y limpieza)

Objetivo:

- Mantener trazabilidad de lo que SI se va a implementar y lo que se puede retirar, sin depender de memoria de equipo.

| Elemento | Existe en codigo | Captura desde UI | Impacto operativo | Decision sugerida | Motivo |
|---|---|---|---|---|---|
| Ubicaciones de almacen | Si (`app/Models/UbicacionAlmacen.php`) | No | Alto | Se queda + Implementar | Ya se usa en compras, insumos y terminados; sin CRUD depende de seed o carga manual |
| Categorias de insumo | Si (`app/Models/CategoriaInsumo.php`) | No | Alto | Se queda + Implementar | Insumos las consume para clasificar y filtrar |
| Unidades de medida | Si (`app/Models/UnidadMedida.php`) | No | Alto | Se queda + Implementar | Base para compras, produccion e inventario |
| Tipos de producto | Si (`app/Models/TipoProducto.php`) | No | Alto | Se queda + Implementar | Nucleo para BOM, produccion, reportes y terminados |
| Configuracion del sistema | Si (`app/Models/ConfiguracionSistema.php`) | No (modulo) | Medio | Se queda + Implementar | Ya se usa para parametros operativos, falta panel de administracion |
| Contactos de proveedor (multi-contacto) | Si (`app/Models/ContactoProveedor.php`) | Parcial | Medio | Se queda + Mejorar | Hoy se maneja contacto principal; falta gestion completa de contactos |
| Bandeja de notificaciones del sistema | Si (`app/Models/NotificacionSistema.php`) | No (modulo visible) | Medio | Se queda + Implementar | El sistema genera alertas, pero falta panel de consulta y estado |
| Compatibilidad legacy (produccion/trazabilidad) | Si | Si (indirecta) | Medio | Se queda temporalmente | Util para datos antiguos; retirar cuando se complete migracion funcional |
| `verify_user.php` | Si | N/A | Medio (seguridad) | Eliminar | Script auxiliar con credenciales hardcodeadas, fuera del flujo final |
| `scripts/debug_entregas_filtro.php` | Si | N/A | Bajo | Eliminar o mover a carpeta de soporte interno | Script de depuracion puntual, no parte del flujo funcional |

Prioridad recomendada de ejecucion:

1. CRUD de catálogos maestros: Ubicaciones, Categorias, Unidades, Tipos de producto.
2. Configuracion del sistema y bandeja de notificaciones.
3. Mejora de contactos de proveedor.
4. Limpieza tecnica final (scripts auxiliares y retiro gradual de legacy/fallback).
