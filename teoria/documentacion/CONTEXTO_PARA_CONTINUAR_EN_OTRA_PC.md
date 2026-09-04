# Contexto para continuar el proyecto en otra PC

## Objetivo

Este repositorio contiene el sistema de gestión y atención al cliente de
**Villafañe Wifi**, desarrollado para Seminario de Integración de la Licenciatura
en Sistemas de Información.

## Decisión de arquitectura vigente

- PHP 8.3 o superior y Laravel 13.
- JavaScript, Blade, CSS y Vite para el panel.
- MariaDB 11.8 como base de datos real.
- Programación orientada a objetos y nombres propios mayormente en español.
- Panel web y API REST central en un monolito modular.

`sistema/` es la única implementación vigente. El prototipo anterior fue
retirado para evitar confusiones con el stack actual.

## Estado funcional

El **alcance actual está implementado**: usuarios y permisos, clientes, planes,
servicios, cuotas, cuentas receptoras, pagos y cuenta corriente. Incluye panel,
API y pruebas. Los requisitos cubiertos son RF-01 a RF-06, RF-29 y RF-30.

Las nueve tablas del dominio implementadas son `usuario`, `administrador`,
`empleado`, `cliente`, `plan`, `servicio`, `cuenta_receptora`, `pago` y `cuota`.
MariaDB también contiene la tabla técnica `migrations` de Laravel.

`Usuario` se especializa de forma total y disjunta en `Empleado` o
`Administrador`; no existe una entidad `Rol`. La aplicación valida esa regla y
aplica permisos según subtipo, área y atributos del administrador.

## Diagramas autoritativos

Los archivos recibidos del equipo están preservados sin eliminar entidades en
`teoria/diagramas/`:

- `DER_logico.mwb` y su PNG.
- `Diagrama_de_clases.drawio` y su PNG.
- `Diagrama_casos_de_uso.drawio` y su PNG.

El DER y el diagrama de clases contienen cinco entidades futuras:
`conversacion`, `mensaje`, `comprobante`, `ticket` y `nota_interna`. Deben seguir
allí hasta que se desarrollen en módulos posteriores. `pago.id_comprobante`
queda nullable como punto de ampliación futura.

## Continuar en otra PC

1. Instalar PHP, Composer, Node.js/npm y MariaDB.
2. Clonar el repositorio y entrar en `sistema/`.
3. Copiar `.env.example` como `.env`.
4. Crear la base `villafane_wifi` y completar las variables `DB_*`.
5. Definir un usuario y contraseña iniciales mediante las variables
   `ADMIN_INICIAL_*`; las contraseñas reales nunca se suben al repositorio.
6. Ejecutar:

```powershell
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan test
php artisan serve
```

El panel se abre en `http://127.0.0.1:8000/iniciar-sesion`.

## Regla para cualquier cambio

Antes de programar, revisar este archivo, `README.md`, los requisitos y
`teoria/documentacion/TRAZABILIDAD_MODELO_DATOS_Y_CLASES.md`, y ejecutar las pruebas. Todo cambio
de una entidad o regla debe revisarse también en la migración, la base, el DER,
el diagrama de clases y la documentación.

El próximo bloque sugerido es conversaciones y mensajes de WhatsApp. Luego se
abordarán comprobantes, soporte, reportes e integraciones.
