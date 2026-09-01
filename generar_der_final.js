const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const outputDir = path.join(process.cwd(), 'diagramas');
fs.mkdirSync(outputDir, { recursive: true });

const esc = (v) => String(v)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;');

const styles = {
  entity: 'rounded=0;whiteSpace=wrap;html=1;fillColor=#dbeafe;strokeColor=#245a9b;strokeWidth=3;fontStyle=1;fontSize=18;',
  subtype: 'rounded=0;whiteSpace=wrap;html=1;fillColor=#dcfce7;strokeColor=#2e7d32;strokeWidth=3;fontStyle=1;fontSize=18;',
  relation: 'rhombus;whiteSpace=wrap;html=1;fillColor=#ffedd5;strokeColor=#d97706;strokeWidth=3;fontSize=12;fontStyle=1;',
  attr: 'ellipse;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#64748b;strokeWidth=2;fontSize=13;',
  attrKey: 'ellipse;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#64748b;strokeWidth=2;fontSize=13;fontStyle=4;',
  attrMulti: 'shape=doubleEllipse;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#64748b;strokeWidth=2;fontSize=13;',
  attrDerived: 'ellipse;whiteSpace=wrap;html=1;fillColor=#ffffff;strokeColor=#64748b;strokeWidth=2;fontSize=13;dashed=1;',
  edge: 'edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;endArrow=none;startArrow=none;strokeColor=#475569;strokeWidth=2;fontSize=15;fontStyle=1;labelBackgroundColor=#ffffff;',
  attrEdge: 'edgeStyle=none;html=1;endArrow=none;startArrow=none;strokeColor=#94a3b8;strokeWidth=1;',
  text: 'text;html=1;strokeColor=none;fillColor=none;align=center;verticalAlign=middle;fontSize=14;',
  note: 'shape=note;whiteSpace=wrap;html=1;fillColor=#fff7ed;strokeColor=#d97706;fontSize=13;align=left;spacing=10;'
};

const fullEntities = {
  plan: { label: 'PLAN', x: 300, y: 600, attrs: [
    ['ID plan', 'key', 300, 345], ['Nombre', 'simple', 40, 470],
    ['Velocidad', 'simple', 40, 735], ['Precio vigente', 'simple', 300, 855],
    ['Estado', 'simple', 555, 735]
  ]},
  servicio: { label: 'SERVICIO', x: 1400, y: 600, attrs: [
    ['ID servicio', 'key', 1400, 345],
    ['Calle de instalación', 'simple', 855, 150],
    ['Número de instalación', 'simple', 1085, 130],
    ['Localidad de instalación', 'simple', 1315, 150],
    ['Próximo vencimiento', 'derived', 1645, 230], ['Día de vencimiento', 'simple', 1765, 385],
    ['Fecha de alta', 'simple', 1040, 855], ['IP', 'simple', 1300, 940],
    ['MAC', 'simple', 1560, 940], ['Estado', 'simple', 1815, 855]
  ]},
  cliente: { label: 'CLIENTE', x: 2700, y: 600, attrs: [
    ['ID cliente', 'key', 2700, 345],
    ['Tipo de documento', 'simple', 2200, 150],
    ['Número de documento', 'simple', 2425, 130],
    ['Nombre o razón social', 'simple', 2960, 285], ['Tipo de cliente', 'simple', 3190, 330],
    ['Teléfono', 'multi', 3190, 520],
    ['Calle de contacto', 'simple', 2180, 1000],
    ['Número de contacto', 'simple', 2415, 1020],
    ['Localidad de contacto', 'simple', 2650, 1000],
    ['Estado', 'simple', 2960, 855]
  ]},
  conversacion: { label: 'CONVERSACIÓN', x: 4050, y: 600, attrs: [
    ['ID conversación', 'key', 4050, 345], ['Número de WhatsApp', 'simple', 3700, 275],
    ['Fecha y hora de inicio', 'simple', 4300, 275], ['Fecha y hora de cierre', 'simple', 4560, 470],
    ['Estado', 'simple', 4310, 855], ['Modo de atención', 'simple', 3760, 855]
  ]},
  usuario: { label: 'USUARIO', x: 5380, y: 300, attrs: [
    ['ID usuario', 'key', 5380, 70], ['Nombre de usuario', 'simple', 5070, 165],
    ['Credencial', 'simple', 5640, 165], ['Estado', 'simple', 5700, 430]
  ]},
  empleado: { label: 'EMPLEADO', x: 5070, y: 900, subtype: true, attrs: [
    ['Área', 'simple', 5070, 1070]
  ]},
  administrador: { label: 'ADMINISTRADOR', x: 5700, y: 900, subtype: true, attrs: [] },
  mensaje: { label: 'MENSAJE', x: 4050, y: 1400, attrs: [
    ['ID mensaje', 'key', 4050, 1180], ['Fecha y hora', 'simple', 3710, 1210],
    ['Tipo', 'simple', 4380, 1210], ['Contenido', 'simple', 4570, 1400],
    ['Archivo adjunto', 'simple', 4380, 1660], ['Tipo de emisor', 'simple', 3710, 1660],
    ['Estado de envío', 'simple', 4050, 1750]
  ]},
  cuota: { label: 'CUOTA', x: 1400, y: 2500, attrs: [
    ['ID cuota', 'key', 1400, 2250], ['Período', 'simple', 1090, 2190],
    ['Monto', 'simple', 1690, 2190], ['Fecha de emisión', 'simple', 1040, 2760],
    ['Fecha de vencimiento', 'simple', 1365, 2845], ['Estado', 'derived', 1710, 2760]
  ]},
  comprobante: { label: 'COMPROBANTE', x: 2700, y: 2000, attrs: [
    ['ID comprobante', 'key', 2700, 1750],
    ['Hash de archivo (único)', 'simple', 3010, 1690], ['Fecha de recepción', 'simple', 3230, 1840],
    ['N.º de operación', 'simple', 2280, 2240], ['Monto OCR', 'simple', 2535, 2360],
    ['Fecha OCR', 'simple', 2810, 2360], ['Confianza OCR', 'simple', 3070, 2300],
    ['Estado de validación', 'simple', 3230, 2160], ['Motivo de rechazo', 'simple', 3230, 1990]
  ]},
  pago: { label: 'PAGO', x: 4050, y: 2500, attrs: [
    ['ID pago', 'key', 4050, 2250], ['Fecha', 'simple', 3710, 2190],
    ['Monto total', 'simple', 4340, 2190], ['Medio de pago', 'simple', 4050, 2780]
  ]},
  cuenta: { label: 'CUENTA RECEPTORA', x: 5400, y: 2500, attrs: [
    ['ID cuenta', 'key', 5400, 2250], ['Nombre', 'simple', 5100, 2190],
    ['Tipo', 'simple', 5700, 2190], ['Identificador', 'simple', 5100, 2780],
    ['Estado', 'simple', 5700, 2780]
  ]},
  ticket: { label: 'TICKET', x: 4050, y: 3500, attrs: [
    ['ID ticket', 'key', 4050, 3245], ['Fecha de creación', 'simple', 3680, 3160],
    ['Tipo', 'simple', 4420, 3160], ['Descripción', 'simple', 3640, 3770],
    ['Estado', 'simple', 4050, 3850], ['Fecha de resolución', 'simple', 4450, 3770]
  ]},
  nota: { label: 'NOTA INTERNA', x: 5400, y: 3500, attrs: [
    ['ID nota', 'key', 5400, 3245], ['Fecha y hora', 'simple', 5100, 3160],
    ['Contenido', 'simple', 5720, 3160]
  ]}
};

const fullRelations = [
  ['plan-servicio', 'SE ASIGNA A / TIENE ASIGNADO', 875, 595, 'plan', 'servicio', '1', 'N'],
  ['servicio-cliente', 'ES CONTRATADO POR / CONTRATA', 2100, 595, 'servicio', 'cliente', 'N', '1'],
  ['cliente-conversacion', 'INICIA / ES INICIADA POR', 3425, 595, 'cliente', 'conversacion', '1', 'N'],
  ['servicio-cuota', 'GENERA / ES GENERADA POR', 1425, 1500, 'servicio', 'cuota', '1', 'N'],
  ['conversacion-mensaje', 'CONTIENE / PERTENECE A', 4050, 1030, 'conversacion', 'mensaje', '1', 'N'],
  ['usuario-mensaje', 'ENVÍA / ES ENVIADO POR', 4950, 1370, 'usuario', 'mensaje', '1', 'N'],
  ['mensaje-comprobante', 'ADJUNTA / ES ADJUNTADO EN', 3375, 1680, 'mensaje', 'comprobante', '1', '1'],
  ['comprobante-pago', 'ORIGINA / SE ORIGINA EN', 3425, 2495, 'comprobante', 'pago', '1', '1'],
  ['pago-cuota', 'CANCELA / ES CANCELADA POR', 2700, 2700, 'pago', 'cuota', '1', 'N'],
  ['cuenta-pago', 'RECIBE / SE RECIBE EN', 4725, 2495, 'cuenta', 'pago', '1', 'N'],
  ['conversacion-ticket', 'GENERA / ES GENERADO EN', 4500, 2900, 'conversacion', 'ticket', '1', 'N'],
  ['servicio-ticket', 'ES AFECTADO POR / AFECTA', 2600, 3150, 'servicio', 'ticket', '1', 'N'],
  ['usuario-conversacion', 'ATIENDE / ES ATENDIDA POR', 4760, 690, 'usuario', 'conversacion', '1', 'N', [['Inicio de atención', 4610, 500], ['Fin de atención', 4795, 430]]],
  ['usuario-comprobante', 'VALIDA / ES VALIDADO POR', 4750, 1850, 'usuario', 'comprobante', '1', 'N', [['Fecha y hora de validación', 4750, 1710]]],
  ['usuario-ticket', 'GESTIONA / ES GESTIONADO POR', 4800, 2950, 'usuario', 'ticket', '1', 'N', [['Fecha de asignación', 5000, 2850]]],
  ['ticket-nota', 'CONTIENE / PERTENECE A', 4800, 3495, 'ticket', 'nota', '1', 'N'],
  ['usuario-nota', 'REDACTA / ES REDACTADA POR', 5550, 2950, 'usuario', 'nota', '1', 'N'],
  ['empleado-usuario', 'ES', 5100, 640, 'empleado', 'usuario', '1', '1'],
  ['administrador-usuario', 'ES', 5680, 640, 'administrador', 'usuario', '1', '1']
];

const overviewEntities = {
  plan: ['PLAN', 40, 90], servicio: ['SERVICIO', 270, 90], cliente: ['CLIENTE', 510, 90],
  conversacion: ['CONVERSACIÓN', 780, 90], mensaje: ['MENSAJE', 1040, 90],
  cuota: ['CUOTA', 270, 390], comprobante: ['COMPROBANTE', 510, 390], pago: ['PAGO', 780, 390],
  cuenta: ['CUENTA RECEPTORA', 1040, 390], ticket: ['TICKET', 780, 720], nota: ['NOTA INTERNA', 1080, 720],
  usuario: ['USUARIO', 1550, 730], empleado: ['EMPLEADO', 1420, 470, true], administrador: ['ADMINISTRADOR', 1690, 470, true]
};

const overviewRelations = [
  ['ov-plan-servicio', 'SE ASIGNA A / TIENE ASIGNADO', 190, 92, 'plan', 'servicio', '1', 'N'],
  ['ov-servicio-cliente', 'ES CONTRATADO POR / CONTRATA', 430, 92, 'servicio', 'cliente', 'N', '1'],
  ['ov-cliente-conversacion', 'INICIA / ES INICIADA POR', 690, 92, 'cliente', 'conversacion', '1', 'N'],
  ['ov-conversacion-mensaje', 'CONTIENE / PERTENECE A', 930, 92, 'conversacion', 'mensaje', '1', 'N'],
  ['ov-servicio-cuota', 'GENERA / ES GENERADA POR', 290, 240, 'servicio', 'cuota', '1', 'N'],
  ['ov-mensaje-comprobante', 'ADJUNTA / ES ADJUNTADO EN', 810, 240, 'mensaje', 'comprobante', '1', '1'],
  ['ov-comprobante-pago', 'ORIGINA / SE ORIGINA EN', 690, 392, 'comprobante', 'pago', '1', '1'],
  ['ov-pago-cuota', 'CANCELA / ES CANCELADA POR', 430, 520, 'pago', 'cuota', '1', 'N'],
  ['ov-cuenta-pago', 'RECIBE / SE RECIBE EN', 930, 392, 'cuenta', 'pago', '1', 'N'],
  ['ov-conversacion-ticket', 'GENERA / ES GENERADO EN', 800, 570, 'conversacion', 'ticket', '1', 'N'],
  ['ov-servicio-ticket', 'ES AFECTADO POR / AFECTA', 520, 680, 'servicio', 'ticket', '1', 'N'],
  ['ov-usuario-mensaje', 'ENVÍA / ES ENVIADO POR', 1280, 250, 'usuario', 'mensaje', '1', 'N'],
  ['ov-usuario-conversacion', 'ATIENDE / ES ATENDIDA POR', 1210, 120, 'usuario', 'conversacion', '1', 'N'],
  ['ov-usuario-comprobante', 'VALIDA / ES VALIDADO POR', 1240, 390, 'usuario', 'comprobante', '1', 'N'],
  ['ov-usuario-ticket', 'GESTIONA / ES GESTIONADO POR', 1230, 610, 'usuario', 'ticket', '1', 'N'],
  ['ov-ticket-nota', 'CONTIENE / PERTENECE A', 960, 722, 'ticket', 'nota', '1', 'N'],
  ['ov-usuario-nota', 'REDACTA / ES REDACTADA POR', 1310, 700, 'usuario', 'nota', '1', 'N'],
  ['ov-empleado-usuario', 'ES', 1420, 580, 'empleado', 'usuario', '1', '1'],
  ['ov-administrador-usuario', 'ES', 1690, 580, 'administrador', 'usuario', '1', '1']
];

function drawioPage(name, id, width, height, entities, relations, includeAttrs) {
  let seq = 1;
  const cells = ['<mxCell id="0"/>', '<mxCell id="1" parent="0"/>'];
  const ids = {};
  const addVertex = (cid, value, style, x, y, w, h) => {
    cells.push(`<mxCell id="${cid}" value="${esc(value)}" style="${style}" vertex="1" parent="1"><mxGeometry x="${x}" y="${y}" width="${w}" height="${h}" as="geometry"/></mxCell>`);
  };
  const addEdge = (value, source, target, style = styles.edge) => {
    cells.push(`<mxCell id="e-${id}-${seq++}" value="${esc(value)}" style="${style}" edge="1" parent="1" source="${source}" target="${target}"><mxGeometry relative="1" as="geometry"/></mxCell>`);
  };

  Object.entries(entities).forEach(([key, e]) => {
    const normalized = Array.isArray(e) ? { label: e[0], x: e[1], y: e[2], subtype: e[3], attrs: [] } : e;
    const cid = `${id}-entity-${key}`;
    ids[key] = cid;
    addVertex(cid, normalized.label, normalized.subtype ? styles.subtype : styles.entity, normalized.x, normalized.y, 220, 80);
  });

  if (includeAttrs) {
    Object.entries(entities).forEach(([key, e]) => {
      (e.attrs || []).forEach((a, idx) => {
        const [label, type, x, y, children] = a;
        const aid = `${id}-attr-${key}-${idx}`;
        const style = type === 'key' ? styles.attrKey : type === 'multi' ? styles.attrMulti : type === 'derived' ? styles.attrDerived : styles.attr;
        const renderedLabel = label;
        addVertex(aid, renderedLabel, style, x, y, 220, 60);
        addEdge('', ids[key], aid, styles.attrEdge);
        (children || []).forEach((child, childIndex) => {
          const childId = `${aid}-child-${childIndex}`;
          addVertex(childId, child[0], styles.attr, child[1], child[2], 160, 50);
          addEdge('', aid, childId, styles.attrEdge);
        });
      });
    });
  }

  relations.forEach((r) => {
    const [key, label, x, y, source, target, sourceCard, targetCard, attrs = []] = r;
    const rid = `${id}-relation-${key}`;
    addVertex(rid, label, styles.relation, x, y, includeAttrs ? 240 : 190, includeAttrs ? 110 : 90);
    addEdge(sourceCard, ids[source], rid);
    addEdge(targetCard, rid, ids[target]);
    attrs.forEach((a, idx) => {
      const aid = `${rid}-attr-${idx}`;
      addVertex(aid, a[0], styles.attr, a[1], a[2], 190, 54);
      addEdge('', rid, aid, styles.attrEdge);
    });
  });

  addVertex(`${id}-specialization-label`, 'Restricción: especialización total y disjunta', styles.text, includeAttrs ? 5260 : 1410, includeAttrs ? 790 : 890, includeAttrs ? 600 : 430, 35);

  addVertex(`${id}-title`, 'DER conceptual — Sistema de Gestión Villafañe Wifi', `${styles.text}fontStyle=1;fontSize=25;`, includeAttrs ? 2100 : 400, 10, includeAttrs ? 2000 : 900, 50);
  if (includeAttrs) {
    addVertex(`${id}-legend`, 'Rectángulo: entidad   ·   Rombo: relación verbal   ·   Óvalo: atributo   ·   Subrayado: identificador   ·   Doble óvalo: multivaluado   ·   Discontinuo: derivado', `${styles.text}fontSize=14;fontColor=#475569;`, 1500, 4250, 4000, 40);
    addVertex(`${id}-note`, 'Lectura de relaciones:\n• La primera frase se lee desde la entidad de origen hacia la entidad destino.\n• La segunda frase se lee en el sentido inverso.\n• Las cardinalidades se expresan exclusivamente con 1 y N, sin ceros.\n• ES representa la especialización total y disjunta; los subtipos heredan ID usuario.\n\nDecisiones del modelo:\n• SERVICIO representa cada conexión contratada.\n• Cada CONVERSACIÓN conserva sus MENSAJES.\n• Un PAGO puede cancelar una o varias CUOTAS; no hay pagos parciales.\n• USUARIO interviene en operaciones humanas para incluir empleados y administradores.\n• La cuenta corriente se obtiene de CUOTA y PAGO.', styles.note, 5850, 3440, 1000, 620);
  } else {
    addVertex(`${id}-note`, 'Vista general. La primera página contiene todos los atributos.', styles.note, 50, 950, 500, 120);
  }

  return `<diagram id="${id}" name="${esc(name)}"><mxGraphModel dx="${width}" dy="${height}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="${width}" pageHeight="${height}" math="0" shadow="0"><root>${cells.join('')}</root></mxGraphModel></diagram>`;
}

const drawio = `<mxfile host="app.diagrams.net" modified="2026-08-28T12:30:00.000Z" agent="Codex" version="24.7.17" type="device">${drawioPage('DER completo con atributos', 'full', 7000, 4400, fullEntities, fullRelations, true)}${drawioPage('Vista general de relaciones', 'overview', 2050, 1150, overviewEntities, overviewRelations, false)}</mxfile>`;

const drawioPath = path.join(outputDir, 'DER_Conceptual_Villafane_Final_V7.drawio');
const xmlPath = path.join(outputDir, 'DER_Conceptual_Villafane_Final_V7.xml');
fs.writeFileSync(drawioPath, drawio, 'utf8');
fs.writeFileSync(xmlPath, drawio, 'utf8');

function centerRect(e) { return [e.x + 110, e.y + 40]; }
function centerRel(r) { return [r[2] + 120, r[3] + 55]; }
const svg = [];
svg.push(`<svg xmlns="http://www.w3.org/2000/svg" width="7000" height="4400" viewBox="0 0 7000 4400">`);
svg.push('<rect width="7000" height="4400" fill="#ffffff"/>');
svg.push('<style>text{font-family:Arial,sans-serif;fill:#1e293b}.edge{stroke:#64748b;stroke-width:3;fill:none}.attr-edge{stroke:#94a3b8;stroke-width:2}.entity{fill:#dbeafe;stroke:#245a9b;stroke-width:4}.subtype{fill:#dcfce7;stroke:#2e7d32;stroke-width:4}.relation{fill:#ffedd5;stroke:#d97706;stroke-width:4}.attr{fill:#fff;stroke:#64748b;stroke-width:3}.derived{stroke-dasharray:10 8}</style>');
svg.push('<text x="3500" y="55" text-anchor="middle" font-size="30" font-weight="700">DER conceptual — Sistema de Gestión Villafañe Wifi</text>');

fullRelations.forEach((r) => {
  const [rx, ry] = centerRel(r); const [sx, sy] = centerRect(fullEntities[r[4]]); const [tx, ty] = centerRect(fullEntities[r[5]]);
  svg.push(`<line class="edge" x1="${sx}" y1="${sy}" x2="${rx}" y2="${ry}"/><line class="edge" x1="${rx}" y1="${ry}" x2="${tx}" y2="${ty}"/>`);
  svg.push(`<text x="${(sx + rx) / 2}" y="${(sy + ry) / 2 - 10}" font-size="22" font-weight="700" text-anchor="middle">${esc(r[6])}</text>`);
  svg.push(`<text x="${(tx + rx) / 2}" y="${(ty + ry) / 2 - 10}" font-size="22" font-weight="700" text-anchor="middle">${esc(r[7])}</text>`);
});

Object.entries(fullEntities).forEach(([key, e]) => {
  (e.attrs || []).forEach((a) => {
    const [label, type, x, y, children] = a; const [ex, ey] = centerRect(e);
    svg.push(`<line class="attr-edge" x1="${ex}" y1="${ey}" x2="${x + 110}" y2="${y + 30}"/>`);
    (children || []).forEach((child) => svg.push(`<line class="attr-edge" x1="${x + 110}" y1="${y + 30}" x2="${child[1] + 80}" y2="${child[2] + 25}"/>`));
  });
});

fullRelations.forEach((r) => {
  const [rx, ry] = centerRel(r);
  (r[8] || []).forEach((a) => svg.push(`<line class="attr-edge" x1="${rx}" y1="${ry}" x2="${a[1] + 95}" y2="${a[2] + 27}"/>`));
});

fullRelations.forEach((r) => {
  const x = r[2], y = r[3];
  const relationParts = r[1].split(' / ');
  svg.push(`<polygon class="relation" points="${x + 120},${y} ${x + 240},${y + 55} ${x + 120},${y + 110} ${x},${y + 55}"/>`);
  svg.push(`<text x="${x + 120}" y="${y + 48}" font-size="15" font-weight="700" text-anchor="middle"><tspan x="${x + 120}">${esc(relationParts[0])}</tspan>${relationParts[1] ? `<tspan x="${x + 120}" dy="22">${esc(relationParts[1])}</tspan>` : ''}</text>`);
  (r[8] || []).forEach((a) => {
    svg.push(`<ellipse class="attr" cx="${a[1] + 95}" cy="${a[2] + 27}" rx="95" ry="27"/>`);
    svg.push(`<text x="${a[1] + 95}" y="${a[2] + 33}" font-size="16" text-anchor="middle">${esc(a[0])}</text>`);
  });
});

Object.entries(fullEntities).forEach(([key, e]) => {
  (e.attrs || []).forEach((a) => {
    const [label, type, x, y, children] = a;
    const cls = `attr${type === 'derived' ? ' derived' : ''}`;
    if (type === 'multi') svg.push(`<ellipse class="${cls}" cx="${x + 110}" cy="${y + 30}" rx="106" ry="26"/>`);
    svg.push(`<ellipse class="${cls}" cx="${x + 110}" cy="${y + 30}" rx="110" ry="30"/>`);
    svg.push(`<text x="${x + 110}" y="${y + 37}" font-size="17" text-anchor="middle"${type === 'key' ? ' text-decoration="underline" font-weight="700"' : ''}>${esc(label)}</text>`);
    (children || []).forEach((child) => {
      svg.push(`<ellipse class="attr" cx="${child[1] + 80}" cy="${child[2] + 25}" rx="80" ry="25"/>`);
      svg.push(`<text x="${child[1] + 80}" y="${child[2] + 31}" font-size="16" text-anchor="middle">${esc(child[0])}</text>`);
    });
  });
  svg.push(`<rect class="${e.subtype ? 'subtype' : 'entity'}" x="${e.x}" y="${e.y}" width="220" height="80"/>`);
  svg.push(`<text x="${e.x + 110}" y="${e.y + 49}" font-size="22" font-weight="700" text-anchor="middle">${esc(e.label)}</text>`);
});

svg.push('<text x="5530" y="835" text-anchor="middle" font-size="18">Restricción: especialización total y disjunta</text>');
svg.push('<text x="3500" y="4275" text-anchor="middle" font-size="18">En cada rombo: primera frase = origen → destino · segunda frase = destino → origen · cardinalidades expresadas solo con 1 y N</text>');
svg.push('<text x="3500" y="4310" text-anchor="middle" font-size="18">Rectángulo: entidad · Rombo: relación verbal · Óvalo: atributo · Subrayado: identificador · Doble óvalo: multivaluado · Discontinuo: derivado</text>');
svg.push('</svg>');

const svgPath = path.join(outputDir, 'DER_Conceptual_Villafane_Final_V7.svg');
fs.writeFileSync(svgPath, svg.join(''), 'utf8');
const pngPath = path.join(outputDir, 'DER_Conceptual_Villafane_Final_V7.png');
console.log(drawioPath);
console.log(xmlPath);
console.log(svgPath);
sharp(Buffer.from(svg.join('')))
  .png()
  .toFile(pngPath)
  .then(() => console.log(pngPath))
  .catch((error) => {
    console.error(error);
    process.exitCode = 1;
  });
