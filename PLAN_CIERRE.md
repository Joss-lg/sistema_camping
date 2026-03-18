# Plan de Cierre - camping_2

## Enfoque de dominio (obligatorio)

Este proyecto queda orientado 100% a productos de acampar. Cualquier ejemplo puntual debe entenderse como referencia tecnica, no como limite funcional del negocio.

Lineamientos de dominio:

- El catalogo de terminados debe representar familias reales de camping (carpas, descanso, cocina outdoor, iluminacion, hidratacion y transporte).
- Las recetas de produccion (BOM) deben usar insumos acordes al rubro outdoor.
- Dashboard, reportes y trazabilidad deben mostrar operaciones del catalogo de camping, no un producto unico de ejemplo.
- Los datos de demostracion deben poblar varios productos de acampar para validar todo el flujo E2E.

## 1) Meta de producto (Definition of Done)
La pagina se considera terminada cuando:

- Un usuario puede iniciar sesion y navegar por modulos sin errores.
- Produccion permite crear y gestionar ordenes de produccion con consumo de insumos.
- Terminados permite registrar stock final y movimientos basicos.
- Trazabilidad permite consultar un lote y ver su historial de etapas.
- Reportes y Dashboard muestran indicadores operativos con filtros por fecha.
- Pruebas criticas pasan en local antes de despliegue.

## 2) Backlog por modulo (priorizado)

### A. Produccion (Prioridad Alta)
Objetivo: operar el flujo principal de fabricacion.

Tareas:
- Definir entidades minimas usadas en pantalla: orden_produccion, uso_material, producto_terminado.
- Implementar en controlador acciones minimas: listar, crear orden, actualizar estado.
- Implementar validacion de stock antes de registrar consumo.
- Registrar consumo de material y actualizar stock de material.
- Construir vista con formulario de alta y tabla de seguimiento.
- Agregar prueba feature de crear orden y registrar consumo.

Criterio de cierre:
- Se crea una orden.
- Se registra consumo valido.
- Si no hay stock suficiente, el sistema bloquea y muestra mensaje claro.

### B. Terminados (Prioridad Alta)
Objetivo: controlar salida de producto final.

Tareas:
- Implementar listado de productos/lotes terminados.
- Implementar registro de ingreso de terminados desde una orden finalizada.
- Implementar ajuste basico de stock de terminado (manual, auditado).
- Agregar filtro por categoria/estado.
- Agregar prueba feature de alta y ajuste de stock.

Criterio de cierre:
- Se puede registrar terminado y ver stock actualizado.

### C. Trazabilidad (Prioridad Alta)
Objetivo: seguir el historial de un lote end-to-end.

Tareas:
- Definir consulta principal por numero de lote o ID.
- Mostrar linea de tiempo con etapas: compra/ingreso, produccion, terminado.
- Enlazar evidencias minimas (fecha, usuario, observacion).
- Agregar vista de detalle de lote.
- Agregar prueba feature de consulta de trazabilidad.

Criterio de cierre:
- Un lote devuelve historial coherente en una sola vista.

### D. Reportes (Prioridad Media)
Objetivo: entregar visibilidad operativa para decisiones.

Tareas:
- Reporte de compras/entregas por rango de fechas.
- Reporte de insumos bajo minimo.
- Reporte de produccion y terminados por periodo.
- Exportacion simple (CSV) para 1 o 2 reportes clave.
- Prueba feature de filtro por fechas.

Criterio de cierre:
- Se pueden filtrar datos reales y exportar al menos un reporte.

### E. Dashboard (Prioridad Media)
Objetivo: resumen ejecutivo diario.

Tareas:
- KPIs minimos: entregas pendientes, insumos bajo minimo, ordenes en curso, terminados del dia.
- Tarjetas con enlaces a modulo correspondiente.
- Vista responsive con datos reales.

Criterio de cierre:
- Dashboard abre sin errores y todos los KPI muestran datos reales.

### F. Pulido final UX y calidad (Prioridad Media/Baja)
Objetivo: entrega estable y usable.

Tareas:
- Mensajes de exito/error consistentes en todas las pantallas.
- Validaciones de formularios homologadas (mismo estilo de errores).
- Revisar textos y ortografia en vistas.
- Revisar paginacion/filtros en tablas principales.
- Pruebas de humo manuales por modulo.

Criterio de cierre:
- Experiencia consistente y sin bloqueos visibles.

## 3) Orden de ejecucion recomendado

1. Produccion
2. Terminados
3. Trazabilidad
4. Reportes
5. Dashboard
6. Pulido final

## 4) Sprint sugerido (7 a 10 dias)

- Dia 1-2: Produccion
- Dia 3-4: Terminados
- Dia 5: Trazabilidad
- Dia 6: Reportes
- Dia 7: Dashboard
- Dia 8-10: QA, correcciones, pruebas y despliegue

## 5) Checklist diario de avance

- Seleccionar 1 tarea alta y 1 media.
- Implementar backend + vista minima del bloque.
- Probar flujo manual completo.
- Ejecutar pruebas automatizadas.
- Dejar commit con mensaje claro.

## 6) Checklist de pre-despliegue

- php artisan test
- php artisan migrate:status
- Verificar .env de produccion (APP_ENV, APP_DEBUG, DB, APP_URL)
- Verificar permisos de storage y bootstrap/cache
- Configurar colas si aplica
- Revisar logs sin errores criticos

## 7) Primer paso para hoy (accion inmediata)

Comenzar por Produccion con este mini alcance:

- Crear orden de produccion.
- Cambiar estado (PENDIENTE, EN_PROCESO, FINALIZADA).
- Registrar consumo de un material.
- Reflejar impacto de stock.

Cuando este bloque quede listo, recien pasar a Terminados.
