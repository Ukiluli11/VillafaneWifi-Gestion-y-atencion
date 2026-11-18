# Sistema de gestión integral y atención al cliente para Villafañe Wifi
## Manual de usuario completo — Guía integral de operación por módulos

**Facultad de Ingeniería, Tecnología y Arquitectura**  
**Carrera:** Licenciatura en Sistemas de Información  
**Materia:** Seminario de Integración — **Comisión:** Única — **Sede:** Formosa  
**Ciclo lectivo:** 2026 — **Docente:** Dr. Cristian Fernando Cerquand  
**Alumnos:** Belazquez, Agustín Lautaro (46.066.193) y Serrano, Ulises Iván (46.321.938)

---


## 1. Introducción general y acceso al sistema


### 1.1 Propósito y alcance del sistema

El Sistema de Gestión Integral y Atención al Cliente para Villafañe Wifi es una plataforma tecnológica integral desarrollada para optimizar, automatizar y auditar todas las operaciones comerciales, de cobranza, atención al cliente y soporte técnico de la empresa de telecomunicaciones en Mayor Villafañe y zonas aledañas.

La solución unifica seis módulos funcionales comunicados de manera sincrónica sobre una única base de datos: la administración de clientes y cobranzas, la atención automatizada con IA y escalamiento humano por WhatsApp, la conciliación bancaria mediante reconocimiento óptico de caracteres (OCR), el soporte técnico mediante tickets de avería, el cuadro de mando gerencial con reportes ejecutivos en PDF y Excel, y la seguridad perimetral con control de accesos por roles (RBAC).


### 1.2 Arquitectura modular unificada

El sistema está estructurado en seis módulos integrados que comparten la misma base transaccional:

- Módulo 1 — Gestión de Clientes, Planes, Servicios y Cuenta Corriente: Control centralizado del padrón de abonados, contrataciones de planes, direcciones de instalación y balance financiero de cuotas.
- Módulo 2 — Canal de Atención Inteligente por WhatsApp: Bot conversacional integrado con Meta Cloud API para autogestión de deudas, recepción de comprobantes, apertura de reclamos y panel para atención humana.
- Módulo 3 — Conciliación de Pagos y Reconocimiento Tesseract OCR: Auditoría de transferencias bancarias mediante visión artificial (OCR), validación de importes, prevención de duplicados con SHA-256 y acreditación contable automática.
- Módulo 4 — Soporte Técnico y Gestión de Tickets de Avería: Seguimiento operativo de incidentes, asignación a personal técnico, bitácora de notas internas confidenciales y notificación de cierre vía WhatsApp.
- Módulo 5 — Métricas, Reportes y Dashboard Ejecutivo: Tablero de control en tiempo real con gráficos Chart.js, cálculo de morosidad en días, rendición de cobranzas, alertas preventivas y exportación formal en PDF y Excel.
- Módulo 6 — Usuarios, Roles y Seguridad: Gestión de cuentas de empleados, políticas de contraseñas seguras y asignación granular de permisos basada en roles (RBAC).

### 1.3 Acceso a la plataforma web

Para ingresar al sistema, el usuario debe abrir un navegador web moderno (Google Chrome, Microsoft Edge o Mozilla Firefox) e ingresar a la dirección del servidor:

- URL del sistema: https://vps-6524456-x.dattaweb.com
La pantalla de inicio de sesión solicita el nombre de usuario y la contraseña asignada por la administración:


![Figura: Pantalla institucional de inicio de sesión en el sistema hosteado](./capturas_manual_completo/01_login.png)
*Figura: Pantalla institucional de inicio de sesión en el sistema hosteado*

Pasos para iniciar sesión:

- 1. Ingrese su Nombre de usuario en el primer campo de texto.
- 2. Ingrese su Contraseña en el segundo campo. Puede utilizar el botón de alternancia para verificar los caracteres ingresados.
- 3. Presione el botón verde 'Ingresar al sistema'.
- 4. El sistema verificará sus credenciales de forma cifrada y lo redirigirá al Panel Principal correspondiente a su perfil de acceso.

> **Nota:** Si ingresa credenciales erróneas de manera consecutiva, el sistema emitirá un aviso visual en rojo y no permitirá el acceso hasta que se corrijan los datos.


### 1.4 Panel principal y navegación del sistema

Al autenticarse con éxito, se despliega el Panel de Inicio (Dashboard de operaciones). Este entorno ofrece una visión panorámica e inmediata del estado del ISP:


![Figura: Panel principal de inicio con accesos rápidos e indicadores operativos](./capturas_manual_completo/02_panel_principal.png)
*Figura: Panel principal de inicio con accesos rápidos e indicadores operativos*

Componentes clave de la interfaz:

- Menú Lateral de Navegación: Ubicado a la izquierda, contiene los accesos directos a Clientes, Planes, Conversaciones, Comprobantes, Cuentas Receptoras, Tickets, Reportes y Usuarios.
- Barra Superior: Muestra el nombre y rol del usuario en sesión, el selector de modo oscuro/claro y el botón seguro de 'Cerrar sesión'.
- Tarjetas de Métricas Rápidas: Contadores destacados con la cantidad de clientes activos, comprobantes pendientes de validación, tickets técnicos sin resolver y cobranzas del día.
- Accesos Rápidos Directos: Botones para registrar un nuevo cliente, cargar un comprobante o emitir un reclamo urgente sin navegar por submenús.

### 1.5 Perfiles de usuario y matriz de permisos

La seguridad del sistema opera bajo el principio de mínimo privilegio. Cada empleado posee un rol acorde a sus funciones laborales:


| Rol / Área | Módulos Habilitados | Facultades Operativas Principales | Restricciones Clave |
| --- | --- | --- | --- |
| Administrador General | Todos (Módulos 1 al 6) | Control total: creación de usuarios, cambio de planes, auditoría contable, exportación de reportes. | Ninguna restricción. |
| Empleado de Administración | Módulos 1, 3, 5 y 6 parcial | Altas de clientes, cobro de cuotas, conciliación OCR de transferencias y descarga de balances. | No puede alterar contraseñas de otros usuarios ni eliminar planes. |
| Técnico de Soporte | Módulos 4 y 1 (consulta) | Atención de reclamos, registro de notas técnicas en campo, cambio de estado a En Proceso y Resolución. | Sin acceso a comprobantes bancarios, facturación ni reportes gerenciales. |
| Operador de Atención al Cliente | Módulos 2 y 4 (apertura) | Toma de chats de WhatsApp escalados por el bot, respuestas directas a abonados y apertura de reclamos. | No puede imputar pagos ni modificar parámetros de red. |


## 2. Módulo 1 – Gestión de clientes, planes, servicios y facturación


### 2.1 Propósito y ciclo de vida del cliente

El Módulo 1 constituye el núcleo de gestión comercial y técnica del ISP. Permite registrar integralmente a los abonados (personas particulares y comercios), asociarles servicios con direcciones geográficas de conexión, asignar direcciones IP y direcciones físicas MAC para el ruteo de red, y administrar su cuenta corriente con emisión mensual de cuotas y recepción de pagos.


### 2.2 Consulta y búsqueda de abonados

Al acceder a la sección 'Clientes' desde el menú lateral, se presenta la bandeja general de clientes:


![Figura: Bandeja principal de clientes con búsqueda interactiva y estados de conexión](./capturas_manual_completo/03_m1_clientes_listado.png)
*Figura: Bandeja principal de clientes con búsqueda interactiva y estados de conexión*

Funcionalidades de la bandeja de clientes:

- Buscador Dinámico: Permite filtrar clientes al instante ingresando nombre o razón social, número de documento (DNI o CUIT), localidad o número de WhatsApp.
- Filtro por Estado: Permite conmutar la visualización entre 'Todos', 'Activos', 'Suspendidos' o 'De baja'.
- Columnas Informativas: Muestra Documento, Nombre/Razón Social, Teléfono WhatsApp, Localidad, Planes contratados, Saldo de cuenta corriente y Estado.
- Botones de Acción Rápida: Permite abrir la 'Ficha' técnica, ingresar directamente a la 'Cuenta corriente' o 'Editar' los datos de contacto.

| Estado del Cliente | Insignia Visual | Impacto Operativo en el Servicio | Condición de Reactivación |
| --- | --- | --- | --- |
| Activo | Verde brillante | El cliente posee servicio de internet habilitado y emite cuotas normalmente. | Estado predeterminado de alta. |
| Suspendido | Amarillo / Naranja | Servicio cortado provisionalmente por falta de pago o pedido temporal de vacaciones. | Se reactiva de inmediato al acreditarse los pagos pendientes. |
| De Baja | Gris oscuro | Contrato finalizado y servicio retirado. No emite nuevas cuotas mensuales. | Requiere nueva solicitud formal de reconexión. |


### 2.3 Paso a paso: Alta completa de un nuevo cliente

Para dar de alta a un abonado junto con su servicio de conectividad a internet:

- 1. En la bandeja de clientes, haga clic en el botón verde '+ Nuevo Cliente' ubicado en la esquina superior derecha.
- 2. Se desplegará el formulario unificado de alta integral:

![Figura: Formulario de alta integral de cliente con datos comerciales y técnicos](./capturas_manual_completo/04_m1_clientes_nuevo.png)
*Figura: Formulario de alta integral de cliente con datos comerciales y técnicos*

- 3. Sección Datos del Titular: Ingrese el Nombre completo o Razón Social, seleccione el Tipo de Documento (DNI, CUIT, Pasaporte), escriba el Número de Documento y el Teléfono de WhatsApp con código de área (ejemplo: 5493704998877).
- 4. Sección Domicilio de Facturación: Ingrese Calle, Número de puerta, Barrio y Localidad (Villafañe, El Colorado, Pirané, Villa Dos Trece).
- 5. Sección Servicio y Conexión Inicial: Seleccione el Plan de Internet a contratar (ej. Hogar 20 Mbps, Hogar Plus 50 Mbps o Comercio 100 Mbps).
- 6. Sección Parámetros de Red: Ingrese la Dirección IPv4 fija asignada en el nodo, la dirección física MAC del módem CPE del cliente y el día del mes pactado para el vencimiento de la cuota (ej. día 10 de cada mes).
- 7. Presione el botón 'Guardar Cliente'.
El sistema validará que el documento y la dirección IP no se encuentren duplicados en la red. Si los datos son conformes, el cliente quedará registrado en estado Activo y con su servicio listo para facturación.


### 2.4 Ficha integral del cliente

Haciendo clic en el nombre del cliente o en el botón 'Ver Ficha', se ingresa al expediente digital del abonado:


![Figura: Ficha técnica y administrativa de abonado con historial de servicios y accesos directos](./capturas_manual_completo/05_m1_clientes_ficha.png)
*Figura: Ficha técnica y administrativa de abonado con historial de servicios y accesos directos*

Contenido de la Ficha:

- Datos de Contacto: DNI/CUIT, número de WhatsApp verificado para notificaciones automáticas y dirección postal.
- Servicios Contratados: Tarjeta detallada de cada conexión activa, indicando velocidad del plan, precio vigente, dirección IP, MAC address y fecha de alta.
- Estado de Cobranza: Resumen del saldo actual, total vencido y fecha del próximo vencimiento programado.
- Acceso a Acciones Rápidas: Botones para Suspender temporalmente el servicio por vacaciones o mora, Reactivar la conexión, Editar datos filiatorios o Acceder a su Cuenta Corriente.

### 2.5 Cuenta corriente y cobro de cuotas

Al ingresar en 'Cuenta corriente', el sistema presenta el libro contable individual del cliente:


![Figura: Cuenta corriente del cliente con detalle de cuotas emitidas, pagos acreditados y balance](./capturas_manual_completo/06_m1_cuenta_corriente.png)
*Figura: Cuenta corriente del cliente con detalle de cuotas emitidas, pagos acreditados y balance*

Operaciones posibles en la Cuenta Corriente:

- 1. Emisión de Cuotas: Cada mes calendario, el sistema genera automáticamente el período fiscal correspondiente al precio del plan. También se puede generar una cuota manual mediante el botón 'Generar Cuota'.
- 2. Registro Manual de Pago: Cuando el cliente abona en efectivo en la oficina o realiza un depósito directo no bancarizado, el operador presiona 'Registrar Pago', indica el monto percibido, la fecha y el medio de cobro (Efectivo/Caja).
- 3. Imputación Cronológica: Los pagos aplican primero a la cuota vencida más antigua, saldando los intereses o deudas pendientes y actualizando el estado a 'Pagada'.
- 4. Tabla de Cuotas y Recibos: Cada fila expone el período (ej. 2026-10), fecha de vencimiento, monto, estado (Pagada en verde, Pendiente en amarillo, Vencida en rojo) y comprobante asociado.

| Estado de la Cuota | Insignia Visual | Definición Financiera | Tratamiento en el Sistema |
| --- | --- | --- | --- |
| Pendiente | Amarillo / Ámbar | Cuota emitida cuyo plazo de vencimiento pactado aún no ha expirado. | Visible en cuenta corriente y computada en el próximo vencimiento. |
| Vencida | Rojo | Cuota impaga cuya fecha de vencimiento ya fue superada. | Genera saldo en mora, cálculo de días de atraso y activa alertas por WhatsApp. |
| Pagada | Verde | Cuota cancelada en su totalidad mediante pago acreditado. | Saldada y cerrada contablemente con ID de pago vinculado. |


### 2.6 Catálogo y administración de planes de internet

Desde la opción 'Planes' del menú lateral se gestionan las ofertas comerciales de conectividad:


![Figura: Catálogo de planes de conectividad con tarifas, anchos de banda y control de estado](./capturas_manual_completo/07_m1_planes_gestion.png)
*Figura: Catálogo de planes de conectividad con tarifas, anchos de banda y control de estado*

Paso a paso para administrar planes:

- Crear un nuevo plan: Hacer clic en '+ Nuevo Plan', definir nombre comercial (ej. Fibra 100 Mbps Simétrica), velocidad de bajada/subida y precio de abono mensual.
- Actualizar precio: Al modificar la tarifa de un plan vigente, las cuotas futuras se emitirán con el nuevo valor sin alterar las cuotas históricas ya emitidas.
- Desactivar plan: Si un plan deja de comercializarse, se puede marcar como inactivo; los clientes que ya poseían ese plan seguirán disfrutándolo pero no se ofrecerá a nuevos abonados.

## 3. Módulo 2 – Canal de atención inteligente por WhatsApp


### 3.1 Propósito y arquitectura conversacional

El Módulo 2 integra a Villafañe Wifi con la plataforma oficial Meta WhatsApp Cloud API. Permite que los abonados interactúen las 24 horas del día mediante mensajes naturales desde sus teléfonos móviles. Un bot conversacional con procesamiento de intenciones responde automáticamente consultas de deuda, recibe fotos de transferencias bancarias y abre reclamos de avería. Si el cliente requiere asistencia personalizada, el sistema escala la conversación a la bandeja de operadores humanos.


### 3.2 Bandeja de conversaciones

Desde el menú lateral, los operadores administrativos y de soporte acceden a la sección 'Conversaciones':


![Figura: Bandeja general de conversaciones de WhatsApp con estados y seguimiento en vivo](./capturas_manual_completo/08_m2_conversaciones_bandeja.png)
*Figura: Bandeja general de conversaciones de WhatsApp con estados y seguimiento en vivo*

Clasificación de estados de conversación:

- Abierta (Bot activo): La conversación está siendo atendida de forma 100% automatizada por el bot. El cliente recibe respuestas inmediatas a sus consultas de menú.
- En atención (Operador asignado): Un empleado humano tomó la conversación. Las respuestas automáticas del bot se suspenden para permitir un diálogo personalizado y fluido.
- Cerrada: La consulta o trámite fue completado y archivado. Si el cliente vuelve a escribir, se abrirá un nuevo ciclo de conversación limpio.

### 3.3 Interacción en tiempo real y auto-servicio

Haciendo clic en 'Abrir →' sobre cualquier chat, se visualiza el hilo cronológico completo de la interacción:


![Figura: Historial cronológico de chat de WhatsApp con identificación del abonado](./capturas_manual_completo/09_m2_conversacion_chat.png)
*Figura: Historial cronológico de chat de WhatsApp con identificación del abonado*


| Opción / Palabra Clave | Intención Procesada | Acción Automática del Sistema | Módulo Destino |
| --- | --- | --- | --- |
| 1 / Saldo / Deuda | Consulta de Cuenta Corriente | Calcula deuda total, cuotas vencidas y devuelve CBU / Alias para pagar. | Módulo 1 (Cuentas) |
| 2 / Pagar / Comprobante | Envío de Pago Bancario | Solicita imagen o PDF de la transferencia y la deriva para OCR. | Módulo 3 (Conciliación) |
| 3 / Reclamo / Avería | Apertura de Incidente Técnico | Pide descripción de la falla y genera un ticket numerado para soporte. | Módulo 4 (Tickets) |
| 4 / Operador / Ayuda | Escalamiento Humano | Suspende el bot, asigna la conversación a la bandeja y avisa a los operadores. | Módulo 2 (Atención) |

Flujos automatizados disponibles para el cliente:

- 1. Identificación y Saludo: Al enviar un primer mensaje (ej. 'Hola'), el bot verifica el número emisor en la base de datos de clientes. Si coincide, saluda al titular por su nombre y presenta el menú interactivo.
- 2. Opción 1 — Consulta de Estado de Cuenta: El bot calcula de inmediato el saldo exigible, las cuotas adeudadas, la fecha del próximo vencimiento y los datos bancarios (CBU y Alias de Mercado Pago) para realizar el pago.
- 3. Opción 2 — Envío de Comprobante de Pago: El cliente adjunta una foto o PDF de su comprobante de transferencia. El bot lo recibe, lo deriva automáticamente al Módulo 3 de Conciliación OCR y le informa que será auditado a la brevedad.
- 4. Opción 3 — Registro de Reclamo Técnico: El abonado describe su inconveniente (ej. 'no tengo internet, luz roja en el módem'). El bot genera un ticket en el Módulo 4 y le entrega el número de seguimiento al cliente.
- 5. Opción 4 — Hablar con un Operador: El cliente solicita atención de una persona. El bot cambia el estado del chat a 'Escalada / En atención' y alerta a los operadores en el panel.

### 3.4 Atención personalizada por operador humano (Takeover)

Cuando una conversación requiere intervención de un empleado de Villafañe Wifi:


![Figura: Panel de atención personalizada por operador humano con caja de respuesta directa](./capturas_manual_completo/10_m2_atencion_humana.png)
*Figura: Panel de atención personalizada por operador humano con caja de respuesta directa*

Pasos para atender a un cliente:

- 1. En la bandeja de conversaciones, localice el chat que requiere atención (marcado con insignia de operador).
- 2. Si el chat está sin asignar, haga clic en el botón 'Tomar conversación'. A partir de este momento, el bot se desactiva y el sistema envía un mensaje al WhatsApp del cliente notificándole qué operador lo está atendiendo.
- 3. Escriba su respuesta en el cuadro 'Escribir mensaje al cliente...' y presione 'Enviar respuesta'. El mensaje saldrá en tiempo real hacia el WhatsApp del usuario.
- 4. Una vez aclaradas las dudas o resuelto el inconveniente, presione el botón rojo 'Cerrar conversación' para registrar el fin del contacto y reactivar el bot para futuros mensajes.

## 4. Módulo 3 – Conciliación de pagos y reconocimiento Tesseract OCR


### 4.1 Ciclo de conciliación y seguridad antifraude

El Módulo 3 automatiza la verificación y acreditación de las transferencias bancarias enviadas por los abonados vía WhatsApp o presentadas en ventanilla. Emplea un motor de visión artificial basado en Tesseract OCR que extrae automáticamente el monto pagado, la fecha de transferencia y el número de transacción. Asimismo, genera un hash SHA-256 sobre cada archivo recibido para detectar intentos de fraude con comprobantes adulterados o reenviados dos veces.


### 4.2 Bandeja de comprobantes recibidos

Al ingresar a 'Comprobantes' desde el menú lateral, se visualiza la mesa de entrada de pagos por conciliar:


![Figura: Bandeja de comprobantes para conciliación bancaria con estados e importes](./capturas_manual_completo/11_m3_comprobantes_bandeja.png)
*Figura: Bandeja de comprobantes para conciliación bancaria con estados e importes*


| Estado del Comprobante | Insignia Visual | Significado Operativo | Acción Posterior en el Sistema |
| --- | --- | --- | --- |
| Pendiente | Amarillo / Ámbar | Transferencia recibida a la espera de validación bancaria y auditoría OCR. | El operador debe inspeccionar y decidir Aprobación o Rechazo. |
| Aprobado | Verde | Transferencia corroborada contra el extracto bancario del ISP. | Genera un Pago automático e imputa cuotas en cuenta corriente. |
| Rechazado | Rojo | Comprobante inválido, ilegible, no acreditado en cuenta o apócrifo. | Guarda el motivo justificado y archiva el registro para auditoría. |


### 4.3 Carga manual de comprobantes

Para comprobantes recibidos por correo electrónico o impresos en ventanilla física:

- 1. Haga clic en '+ Nuevo Comprobante' en la bandeja principal.
- 2. Se abre el formulario de carga:

![Figura: Formulario de carga manual de comprobante de pago con procesamiento OCR](./capturas_manual_completo/12_m3_comprobantes_nuevo.png)
*Figura: Formulario de carga manual de comprobante de pago con procesamiento OCR*

- 3. Seleccione el Cliente titular en el menú desplegable.
- 4. Seleccione la Cuenta Receptora donde ingresaron los fondos (ej. Mercado Pago o Banco).
- 5. Adjunte el archivo de la transferencia (JPG, PNG o PDF legible).
- 6. Puede indicar opcionalmente el monto y número de operación declarados por el cliente.
- 7. Presione 'Procesar Comprobante'. El servidor ejecutará Tesseract OCR de inmediato.

### 4.4 Auditoría asistida por Tesseract OCR

Haciendo clic en 'Ver Detalle' sobre cualquier comprobante de la lista, se abre la mesa de auditoría:


![Figura: Ficha de auditoría asistida por OCR con extracción óptica de datos y hash SHA-256](./capturas_manual_completo/13_m3_comprobante_detalle_ocr.png)
*Figura: Ficha de auditoría asistida por OCR con extracción óptica de datos y hash SHA-256*

Elementos de la mesa de auditoría:

- Visor de Documento: Muestra la imagen o PDF original de la transferencia bancaria con controles de zoom.
- Datos Detectados por OCR: Tabla lateral con la lectura automática de Monto extraído, Fecha detectada, Número de transacción bancaria y Cuenta origen.
- Control Antifraude: Hash SHA-256 calculado sobre el archivo. Si otro cliente intenta reutilizar el mismo comprobante, el sistema emite una advertencia de fraude inmediata.
- Cuotas Pendientes del Cliente: Tabla inferior que muestra las cuotas que el cliente adeuda actualmente, facilitando comparar el importe transferido con la deuda real.

### 4.5 Paso a paso: Aprobación e imputación de pago

Pasos para aprobar una transferencia conforme:

- 1. En la ficha del comprobante, revise el panel 'Aprobar y Acreditar Pago'.
- 2. Seleccione la Cuenta Receptora donde se confirmó el ingreso del dinero (Mercado Pago o Banco).
- 3. Verifique el Medio de pago (Transferencia / Billetera virtual).
- 4. Confirme el Monto a Imputar y la Fecha de Acreditación.
- 5. Presione el botón verde 'Aprobar Comprobante'.
Efectos inmediatos de la aprobación:

- El comprobante pasa al estado 'Aprobado'.
- Se genera automáticamente un Pago en la cuenta corriente del cliente.
- Las cuotas adeudadas se saldan en estricto orden cronológico (de la más antigua a la más nueva).
- Si el comprobante provino de WhatsApp, el bot le envía de inmediato un mensaje de confirmación al abonado informando que su pago fue registrado.

### 4.6 Rechazo justificado de un comprobante

Si la transferencia no existe en la cuenta bancaria del ISP o el archivo está borroso:

- 1. Diríjase a la sección 'Rechazar Comprobante' en la parte inferior de la ficha.
- 2. Ingrese el Motivo del rechazo de forma clara (ej. 'Comprobante ilegible' o 'Monto no acreditado en la cuenta').
- 3. Presione el botón rojo 'Rechazar Comprobante'.
- 4. La decisión queda asentada con fecha, hora y usuario responsable para auditoría interna.

### 4.7 Configuración de cuentas receptoras

En 'Cuentas Receptoras' se definen los canales bancarios oficiales de Villafañe Wifi:


![Figura: Administración de cuentas receptoras de fondos (Mercado Pago, CBU bancarios)](./capturas_manual_completo/14_m3_cuentas_receptoras.png)
*Figura: Administración de cuentas receptoras de fondos (Mercado Pago, CBU bancarios)*

Permite dar de alta cuentas bancarias con su CBU, alias, CUIT titular y cuentas de Mercado Pago. El bot de WhatsApp utiliza estas cuentas activas para informar los datos de depósito a los clientes.


## 5. Módulo 4 – Soporte técnico y gestión de tickets de avería


### 5.1 Ciclo de vida del ticket de avería

El Módulo 4 centraliza la recepción, resolución y seguimiento de los incidentes técnicos y administrativos. Su objetivo es garantizar que ningún reclamo quede desatendido, registrando cada intervención de campo:


| Estado del Ticket | Insignia Visual | Definición Operativa | Acción Responsable |
| --- | --- | --- | --- |
| Pendiente / Abierto | Amarillo / Ámbar | Incidente recién registrado. A la espera de asignación de técnico o inicio de diagnóstico. | El coordinador debe asignar un técnico capacitado. |
| En Proceso | Azul | El técnico tomó el caso y se encuentra ejecutando pruebas o visita de campo. | El técnico registra notas internas con las mediciones. |
| Resuelto | Verde | La avería fue reparada y se asentó la observación técnica final. | El cliente recibe aviso por WhatsApp de solución. |
| Cerrado | Gris | Caso archivado definitivamente tras verificar la estabilidad del enlace. | Fin del ciclo de vida del ticket. |


### 5.2 Bandeja de tickets y filtros multicriterio

Al ingresar a 'Tickets' desde el menú lateral, se presenta la consola de control de soporte:


![Figura: Bandeja de tickets técnicos con indicadores operacionales y filtros multicriterio](./capturas_manual_completo/15_m4_tickets_bandeja.png)
*Figura: Bandeja de tickets técnicos con indicadores operacionales y filtros multicriterio*

Herramientas de la bandeja:

- Métricas Operativas Superiores: Contadores de Tickets Pendientes, En Proceso, Resueltos en el mes y Total acumulado.
- Filtros Combinados: Permite segmentar tickets por Estado (Pendiente, En Proceso, Resuelto), Tipo (Técnico, Administrativo), Técnico Asignado y buscador por nombre de abonado.
- Identificación Visual de Prioridad: Código de colores según el estado y tiempo de espera del caso.

### 5.3 Apertura de un nuevo ticket de soporte

Para abrir un reclamo de forma manual desde la oficina:

- 1. En la bandeja de tickets, presione el botón '+ Nuevo Ticket'.
- 2. Se abre el formulario de registro de incidente:

![Figura: Formulario de registro de nuevo ticket de soporte técnico](./capturas_manual_completo/16_m4_tickets_nuevo.png)
*Figura: Formulario de registro de nuevo ticket de soporte técnico*

- 3. Seleccione el Tipo de Reclamo (Técnico: cortes de enlace, lentitud, router dañado; Administrativo: consultas de facturación).
- 4. Seleccione el Servicio afectado del cliente en el menú desplegable (muestra titular, dirección y plan).
- 5. Asigne opcionalmente un Técnico Responsable desde el momento de la apertura.
- 6. Describa detalladamente el Problema manifestado por el abonado.
- 7. Agregue una Nota Inicial con instrucciones de prueba (ej. 'Revisar alineación de antena').
- 8. Presione 'Crear Ticket'.

### 5.4 Ficha técnica, asignación y bitácora interna

Al presionar sobre el número del ticket, se accede al expediente de trabajo técnico:


![Figura: Ficha de seguimiento de ticket con datos de conexión y bitácora técnica de notas](./capturas_manual_completo/17_m4_ticket_detalle.png)
*Figura: Ficha de seguimiento de ticket con datos de conexión y bitácora técnica de notas*

Secciones del expediente de soporte:

- Ficha de Conectividad: Datos técnicos de la conexión del cliente (IP asignada, MAC del receptor, domicilio exacto de instalación y plan contratado).
- Asignación de Responsable: Selector para cambiar o reasignar el técnico a cargo con confirmación mediante el botón 'Asignar Técnico'.
- Bitácora Técnica y Notas Internas: Espacio seguro donde los técnicos anotan mediciones de potencia óptica, cambios de conectores o pruebas realizadas. Estas notas son confidenciales y no son vistas por el cliente.

### 5.5 Resolución técnica con notificación por WhatsApp

Cuando el desperfecto fue subsanado:

- 1. En la ficha del ticket, el técnico presiona el botón verde 'Resolver Ticket'.
- 2. Se abre el modal interactivo de resolución:

![Figura: Modal de resolución de ticket con redacción técnica y notificación al cliente](./capturas_manual_completo/18_m4_ticket_resolucion.png)
*Figura: Modal de resolución de ticket con redacción técnica y notificación al cliente*

- 3. Escriba la Observación técnica de resolución (ej. 'Se reemplazó conector RJ-45 sulfatado en entrada PoE y se alineó la antena receptora. Parámetros de señal -19 dBm óptimos').
- 4. Marque la casilla 'Notificar por WhatsApp': Al marcarla, el sistema enviará automáticamente un mensaje al teléfono del cliente avisándole que su reclamo ha sido resuelto conforme.
- 5. Presione 'Guardar y Resolver'.

### 5.6 Reapertura de tickets

Si tras la intervención el cliente vuelve a comunicarse manifestando que el problema persiste:

- 1. En la ficha del ticket resuelto, presione el botón naranja 'Reabrir Ticket'.
- 2. Ingrese el Motivo de reapertura justificado.
- 3. El caso vuelve al estado 'Abierto' manteniendo todo el historial previo intacto.

## 6. Módulo 5 – Métricas, reportes y dashboard ejecutivo


### 6.1 Propósito analítico y soporte gerencial

El Módulo 5 dota a la dirección de Villafañe Wifi de una potente herramienta de Business Intelligence (BI) y auditoría contable. Convierte los millones de registros transaccionales en gráficos visuales interactivos, indicadores de cobrabilidad en días completos, rendimiento del soporte técnico y alertas preventivas de vencimiento.


### 6.2 Dashboard general ejecutivo

Al acceder a 'Reportes y Métricas' desde el menú lateral, se despliega el Tablero de Control:


![Figura: Dashboard ejecutivo con tarjetas KPI y gráficos interactivos Chart.js](./capturas_manual_completo/19_m5_dashboard_metricas.png)
*Figura: Dashboard ejecutivo con tarjetas KPI y gráficos interactivos Chart.js*

Indicadores Clave de Rendimiento (KPIs):

- Clientes Activos: Total de conexiones habilitadas en la red.
- Tasa de Morosidad: Porcentaje y número de clientes que adeudan cuotas vencidas.
- Deuda Vencida Total: Monto monetario exacto pendiente de cobro vencido.
- Recaudación del Mes: Total acumulado de pagos acreditados en el período en curso.
- Tickets Abiertos y Comprobantes por Validar: Monitores de carga operativa pendiente.
Gráficos Dinámicos con Chart.js:

- Evolución de Ingresos: Gráfico de líneas que ilustra la recaudación mensual comparativa de los últimos 6 meses.
- Distribución de Clientes por Plan: Gráfico circular (dona) que refleja el porcentaje de abonados por velocidad de abono.
- Estado de Tickets: Gráfico de barras que compara reclamos resueltos frente a incidentes pendientes.

### 6.3 Reporte de clientes y morosidad

Haciendo clic en 'Clientes y Morosidad', se accede a la auditoría de cobranza de la cartera:


![Figura: Reporte de clientes y segmentación de morosidad clasificado por antigüedad de deuda](./capturas_manual_completo/20_m5_reporte_morosidad.png)
*Figura: Reporte de clientes y segmentación de morosidad clasificado por antigüedad de deuda*


| Rango de Antigüedad | Días de Atraso | Severidad de Cartera | Acción Operativa Sugerida |
| --- | --- | --- | --- |
| Al Día | 0 días | Regular / Normal | Servicio operativo sin restricciones. |
| Mora Temprana | 1 a 30 días | Leve | Emisión de recordatorio amistoso vía WhatsApp. |
| Mora Media | 31 a 60 días | Moderada | Notificación formal de corte inminente. |
| Mora Crítica | Más de 60 días | Alta / Incobrable | Suspensión del servicio de red y gestión judicial o baja de conexión. |


### 6.4 Reporte financiero de cobranzas e ingresos

En 'Cobranzas e Ingresos', el área contable efectúa el balance de fondos percibidos:


![Figura: Reporte financiero de cobranzas e ingresos discriminado por medio de cobro](./capturas_manual_completo/21_m5_reporte_ingresos.png)
*Figura: Reporte financiero de cobranzas e ingresos discriminado por medio de cobro*

Herramientas de balance financiero:

- Filtro por Período Rápido: Botones 'Hoy', 'Esta semana' y 'Este mes', o rango entre dos fechas calendario.
- Discriminación por Canal de Cobro: Separa los ingresos ingresados por Transferencia Bancaria, Billetera Virtual (Mercado Pago) o Cobro en Efectivo en caja.
- Métricas de Rendición: Informa el Importe Total Recaudado, Cantidad de Operaciones y Ticket Promedio de cobranza.

### 6.5 Reporte de rendimiento de tickets de soporte

La opción 'Rendimiento de Tickets' evalúa la calidad y celeridad de las reparaciones técnicas:


![Figura: Reporte de rendimiento y tiempos de resolución técnica de reclamos](./capturas_manual_completo/22_m5_reporte_tickets.png)
*Figura: Reporte de rendimiento y tiempos de resolución técnica de reclamos*

Métricas analíticas calculadas:

- Tasa de Resolución: Proporción de reclamos cerrados con éxito sobre el total ingresado.
- Tiempo Medio de Resolución: Promedio de horas transcurridas desde la creación del ticket hasta su solución técnica formal.
- Distribución por Técnico: Tabla comparativa de productividad individual de cada técnico del plantel.

### 6.6 Centro de alertas preventivas de vencimiento

La sección 'Alertas de Vencimiento' actúa como sistema preventivo de cortes de servicio:


![Figura: Centro de alertas de vencimiento y disparo de recordatorios por WhatsApp](./capturas_manual_completo/23_m5_alertas_vencimiento.png)
*Figura: Centro de alertas de vencimiento y disparo de recordatorios por WhatsApp*

Comportamiento de las alertas:

- Próximos a Vencer (7 días): Detecta cuotas impagas cuya fecha límite vencerá en los próximos siete días corridos.
- Ya Vencidos: Lista a los clientes en mora que requieren intimación o corte de enlace.
- Disparo Manual: El botón 'Disparar Aviso WhatsApp' permite enviar un mensaje amigable recordando la cuota y los datos de CBU.
- Automatización Diaria (Cronjob): El sistema evalúa diariamente a las 09:00 hs los vencimientos del día y envía los avisos de forma desatendida.

### 6.7 Exportación multiformato (PDF y Excel)

Los reportes pueden descargarse de forma oficial en formatos estándar:


| Formato de Salida | Motor de Generación | Ventajas Técnicas | Destino Recomendado |
| --- | --- | --- | --- |
| PDF (.pdf) | Dompdf 3.1 | Documento formal vectorizado con membrete institucional, pie de página paginado y firma digital. | Presentaciones gerenciales, archivo contable fiscal y entrega de balances impresos. |
| Excel (.xlsx) | OpenSpout 5.13 | Escritura en streaming directo sin sobrecarga de memoria RAM, tipos numéricos puros y fórmulas. | Auditoría interna, conciliaciones en Excel / LibreOffice y cálculo de comisiones. |
| CSV (.csv) | Streaming nativo UTF-8 | Texto delimitado por comas con firma BOM universal para compatibilidad con sistemas legados. | Importación en sistemas ERP contables externos. |


## 7. Módulo 6 – Control de accesos, usuarios y permisos


### 7.1 Seguridad y arquitectura de permisos (RBAC)

El Módulo 6 garantiza la protección integral de la plataforma mediante autenticación robusta y asignación granular de facultades operativas. Ningún usuario puede ejecutar acciones fuera del perímetro asignado por la gerencia.


### 7.2 Administración de cuentas de usuario

Al acceder a 'Usuarios' desde el menú lateral, los administradores gestionan el plantel de operadores:


![Figura: Listado general de usuarios del sistema con roles y estados asignados](./capturas_manual_completo/24_m6_usuarios_listado.png)
*Figura: Listado general de usuarios del sistema con roles y estados asignados*

Información de la tabla de usuarios:

- Nombre de Usuario: Identificador único de inicio de sesión.
- Rol Asignado: Administrador General o Empleado por Área.
- Estado de la Cuenta: Activo (habilitado para operar) o Desactivado (bloqueo inmediato de credenciales).
- Fecha de Registro: Auditoría de antigüedad de la cuenta.

### 7.3 Alta y edición de permisos granulares

Haciendo clic en 'Editar' o '+ Nuevo Usuario', se abre la ficha de configuración de permisos:


![Figura: Formulario de asignación granular de permisos por módulo y rol operativo](./capturas_manual_completo/25_m6_usuarios_edicion.png)
*Figura: Formulario de asignación granular de permisos por módulo y rol operativo*


| Permiso Granular | Módulo Vinculado | Acciones que Autoriza | Perfil Habitual |
| --- | --- | --- | --- |
| Gestionar Clientes | Módulo 1 | Crear, modificar datos filiatorios y dar de baja clientes. | Administración / Atención |
| Configurar Planes | Módulo 1 | Crear ofertas comerciales y actualizar precios de los abonos. | Gerencia / Administrador |
| Conciliar Cobranzas | Módulo 3 | Aprobar transferencias bancarias e imputar pagos en cuenta corriente. | Administración / Cobranzas |
| Atender WhatsApp | Módulo 2 | Tomar conversaciones escaladas y enviar mensajes directos a abonados. | Atención al Cliente |
| Gestionar Tickets | Módulo 4 | Abrir reclamos, asignar técnicos, asentar notas y resolver incidentes. | Soporte Técnico / Atención |
| Consultar Reportes | Módulo 5 | Visualizar el dashboard ejecutivo y pantallas de métricas. | Todos los perfiles |
| Exportar Reportes | Módulo 5 | Descargar balances formales en formato PDF y hojas de cálculo XLSX. | Gerencia / Administración |
| Gestionar Usuarios | Módulo 6 | Crear cuentas de empleado, asignar roles y desactivar accesos. | Exclusivo Administrador |


### 7.4 Desactivación y preservación de auditoría

Cuando un empleado finaliza su relación laboral con Villafañe Wifi, el Administrador no debe borrar el usuario de la base de datos, sino cambiar su estado a 'Desactivado':

- Efecto Inmediato: El usuario ya no puede iniciar sesión ni acceder al sistema.
- Preservación Histórica: Todos los tickets resueltos, pagos conciliados y comprobantes aprobados en el pasado por dicho usuario conservan su firma para futuras auditorías contables.

### 7.5 Autogestión de contraseñas

Cada usuario puede cambiar su propia contraseña en cualquier momento desde la barra superior ('Mi Cuenta' -> 'Contraseña'). El sistema exige confirmar la contraseña anterior e ingresar una nueva con un mínimo de 8 caracteres.


## 8. Guía rápida de solución de problemas frecuentes

A continuación se presenta la matriz de diagnóstico y resolución de incidentes comunes para orientar a los operadores del sistema ante eventos inesperados:


| Síntoma / Incidencia | Módulo Afectado | Causa Probable | Acción Correctiva Recomendada |
| --- | --- | --- | --- |
| No se puede iniciar sesión ('Credenciales incorrectas') | Acceso / M6 | Contraseña errónea o usuario desactivado por la administración. | Verifique mayúsculas y caracteres especiales. Solicite a un Administrador corroborar si la cuenta está en estado Activo. |
| El bot de WhatsApp no responde a los clientes | Módulo 2 | Servicio Meta Cloud API sin conexión o token de acceso vencido. | Verifique el estado del servidor. Compruebe en el archivo de configuración (.env) que el token permanente de Meta esté vigente. |
| Comprobante recibido no muestra datos en OCR | Módulo 3 | Imagen muy borrosa, captura incompleta o archivo con baja resolución. | Use el visor de comprobante para verificar la transferencia manualmente e ingrese los datos a mano antes de aprobar. |
| Alerta de 'Comprobante Duplicado / Posible Fraude' | Módulo 3 | El hash SHA-256 del comprobante coincide con una transferencia ya aprobada. | Inspeccione el historial del comprobante original. Rechace el comprobante duplicado indicando 'Transferencia ya imputada anteriormente'. |
| El cliente afirma que pagó pero su servicio sigue suspendido | Módulo 1 / 3 | El comprobante está todavía en estado 'Pendiente' en la bandeja de conciliación. | Acceda a 'Comprobantes', localice el archivo del cliente y proceda a Aprobarlo para que se acredite en su cuenta corriente. |
| No aparecen los botones para descargar PDF o Excel | Módulo 5 | El usuario en sesión no posee el permiso 'ExportarReportes'. | Inicie sesión con una cuenta con perfil de Administración o solicite la asignación del permiso en la sección de Usuarios. |
| La tabla de un reporte aparece completamente vacía | Módulo 5 | Los filtros de fecha son muy estrechos o no hubo movimientos en ese lapso. | Presione el botón gris 'Limpiar' para restablecer los filtros predeterminados y consultar el rango mensual. |
| Ticket técnico no notifica al cliente al resolverse | Módulo 4 | No se tildó la casilla 'Notificar por WhatsApp' al momento de guardar la resolución. | Compruebe que el cliente tenga número de WhatsApp registrado y asegúrese de tildar la opción de notificación antes de guardar. |


## 9. Buenas prácticas operativas y de seguridad

- 1. No compartir credenciales: Cada empleado debe utilizar exclusivamente su propio usuario para garantizar la trazabilidad de los cobros y resoluciones técnicas.
- 2. Conciliación diaria: Se recomienda revisar la bandeja de comprobantes al inicio y al final de cada turno para mantener las cuentas corrientes al día.
- 3. Precisión en la bitácora técnica: Los técnicos deben documentar de forma clara los materiales reemplazados y niveles de señal obtenidos para facilitar futuras reparaciones.
- 4. Cierre seguro de sesión: Al finalizar la jornada o abandonar el puesto de trabajo, presionar siempre 'Cerrar sesión' en la barra superior.