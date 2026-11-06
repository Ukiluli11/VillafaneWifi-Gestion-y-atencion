# Actualización técnica del Módulo 5

Fecha de revisión: 5 de noviembre de 2026, hora de Argentina.

Este documento complementa el Informe del Sistema con el comportamiento implementado durante la revisión del módulo. Se publicó en el VPS de Dattaweb el 5 de noviembre de 2026 por la noche, hora argentina, mediante el procedimiento de despliegue existente y un acceso SSH temporal autorizado por el usuario.

| Requerimiento | Implementación revisada |
| --- | --- |
| RF-34 | Reporte filtrado de clientes al día y en mora; atraso expresado en días completos con fecha de Argentina. |
| RF-35 | Rango de fechas, medio de pago y cuenta receptora; accesos rápidos a hoy, semana y mes. |
| RF-36 | Reporte por fecha de creación, estado y responsable; tasa de resolución y tiempo medio en horas. |
| RF-37 | Alertas en el panel y reutilización del servicio de WhatsApp con programación a las 09:00 de Argentina. El envío efectivo depende de su configuración operativa. |
| RF-38 | Indicadores y gráficos del dashboard, incluido el indicador visible de comprobantes por validar. |
| RF-39 | Archivos PDF generados con Dompdf y XLSX con OpenSpout; CSV y vista imprimible conservados para compatibilidad. |

## Diferencias respecto del informe previo

La exportación se concentra en `ExportService`. El PDF se genera con `dompdf/dompdf` 3.1 y el Excel con `openspout/openspout` 5.13. No se utiliza Maatwebsite/Laravel-Excel. Las planillas contienen importes numéricos y totales calculados; no incluyen fórmulas de Excel. OpenSpout requiere PHP 8.4 o superior dentro de las versiones que admite el paquete; el entorno de desarrollo utiliza PHP 8.5. El despliegue debe ejecutar `composer install` desde el archivo de bloqueo e incluir las extensiones exigidas por esas dependencias.

Las consultas del servicio combinan agregaciones SQL y procesamiento de colecciones en PHP. El reporte de morosidad aún carga las cuotas de los clientes y consulta su último pago por cliente; la descripción anterior de agregaciones SQL optimizadas no representa todas las consultas actuales. Para grandes volúmenes conviene medir y optimizar esa consulta antes de ampliar la carga.

El permiso `ExportarReportes` se exige en la ruta de descarga, además de `ConsultarReportes`. Los enlaces se muestran únicamente cuando la política de acceso concede exportación. Administración y Administrador pueden exportar; Soporte y Atención al Cliente conservan consulta.

## Verificación y límites

La revisión visual se realizó con SQLite y los seeders de demostración del repositorio porque el directorio de la base MariaDB local no estaba disponible. Se revisaron dashboard, filtros, estados vacíos, alertas y descargas. En los datos de demostración se verificó una deuda de 1.700 y dos pagos que suman 300. Se inspeccionaron PDFs renderizados y el contenido XML de los archivos XLSX.

Las pruebas automatizadas cubren permisos, consultas, exportaciones de los tres reportes, importes numéricos, totales y conservación de texto. Se añadieron comprobaciones para días completos de atraso, consulta y visibilidad de comprobantes pendientes y cuotas que vencen hoy en Argentina. La suite completa aprobó 108 pruebas con 537 aserciones. Esta revisión no acredita entrega de WhatsApp real.

Después del despliegue se verificaron internamente las cinco pantallas y seis descargas PDF/XLSX sobre MariaDB, con contexto de Administrador y ejecución como `www-data`: las once comprobaciones devolvieron estado 200 y los archivos tuvieron firmas PDF y ZIP/XLSX válidas. La comprobación HTTPS de salud devolvió 200 y se confirmaron activos PHP-FPM, OCR y el temporizador del programador de tareas. Se conservaron los ajustes de CSS, JavaScript y acceso de la versión previamente publicada.

La versión publicada es `20261106T024137Z-34565` y el respaldo previo es `respaldo-20261106T024140Z.tar.gz`; los identificadores están expresados en UTC. La base de datos y la configuración compartida se conservaron y no hubo migraciones nuevas pendientes.

Antes de publicar: conservar el respaldo y la configuración compartida, comprobar la plataforma PHP del VPS, instalar las dependencias, publicar mediante el script existente y comprobar salud HTTP y reportes autenticados. Las alertas de Composer sobre paquetes existentes deben atenderse en una actualización independiente del módulo.

El manual operativo se encuentra en `Manuales/Manual de usuario Modulo 5.md`. El membrete institucional heredado requiere corroboración de los datos fiscales y de contacto antes de utilizarlo externamente.
