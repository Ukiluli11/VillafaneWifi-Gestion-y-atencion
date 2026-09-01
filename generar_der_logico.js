const fs = require('fs');
const path = require('path');

const baseDir = path.join(process.cwd(), 'modelo_logico');
const sqlPath = path.join(baseDir, 'villafane_wifi_mysql_workbench.sql');
const sql = fs.readFileSync(sqlPath, 'utf8');

const esc = (value) => String(value)
  .replace(/&/g, '&amp;')
  .replace(/</g, '&lt;')
  .replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;');

const positions = {
  plan: [50, 80],
  cliente: [560, 80],
  cliente_telefono: [1070, 80],
  usuario: [1630, 80],
  empleado: [2140, 80],
  administrador: [2600, 80],
  servicio: [130, 760],
  conversacion: [700, 760],
  mensaje: [1300, 760],
  comprobante: [1950, 760],
  cuenta_receptora: [2550, 760],
  cuota: [130, 1600],
  pago: [820, 1600],
  ticket: [1510, 1600],
  nota_interna: [2270, 1700],
};

const tables = {};
const createRe = /CREATE TABLE\s+(\w+)\s*\(([\s\S]*?)\) ENGINE=InnoDB;/g;
let match;
while ((match = createRe.exec(sql)) !== null) {
  const [, name, body] = match;
  const columns = [];
  for (const rawLine of body.split(/\r?\n/)) {
    const line = rawLine.trim().replace(/,$/, '');
    if (/^CONSTRAINT\b/i.test(line)) break;
    const column = line.match(/^(\w+)\s+([A-Z]+(?:\([^)]*\))?(?:\s+UNSIGNED)?)(.*)$/i);
    if (!column || /^(CONSTRAINT|PRIMARY|FOREIGN|UNIQUE|CHECK)$/i.test(column[1])) continue;
    columns.push({
      name: column[1],
      type: column[2].replace(/\s+/g, ' '),
      nullable: !/\bNOT NULL\b/i.test(column[3]),
    });
  }
  const primary = new Set();
  const pk = body.match(/PRIMARY KEY\s*\(([^)]+)\)/i);
  if (pk) pk[1].split(',').forEach((c) => primary.add(c.trim()));
  const unique = new Set();
  for (const uq of body.matchAll(/UNIQUE\s*\(([^)]+)\)/gi)) {
    const cols = uq[1].split(',').map((c) => c.trim());
    if (cols.length === 1) unique.add(cols[0]);
  }
  const fks = [];
  for (const fk of body.matchAll(/FOREIGN KEY\s*\((\w+)\)\s+REFERENCES\s+(\w+)\s*\((\w+)\)/gi)) {
    fks.push({ column: fk[1], parent: fk[2], parentColumn: fk[3] });
  }
  tables[name] = { name, columns, primary, unique, fks };
}

for (const table of Object.values(tables)) {
  table.height = 54 + table.columns.length * 27 + 14;
  [table.x, table.y] = positions[table.name];
  for (const column of table.columns) {
    column.pk = table.primary.has(column.name);
    column.fk = table.fks.some((fk) => fk.column === column.name);
    column.uq = table.unique.has(column.name);
  }
}

const pageWidth = 3100;
const pageHeight = 2450;
let seq = 1;
const cells = ['<mxCell id="0"/>', '<mxCell id="1" parent="0"/>'];

for (const table of Object.values(tables)) {
  const rows = table.columns.map((c) => {
    const marks = [c.pk ? 'PK' : '', c.fk ? 'FK' : '', c.uq ? 'UQ' : ''].filter(Boolean).join(', ');
    const nullMark = c.nullable ? 'NULL' : 'NN';
    return `<div style="text-align:left;padding:2px 8px"><b>${esc(marks || '·')}</b>  ${esc(c.name)} : ${esc(c.type)}  <font color="#64748b">${nullMark}</font></div>`;
  }).join('');
  const value = `<div style="background:#1f4e78;color:#ffffff;font-weight:bold;font-size:16px;padding:8px;text-align:center">${esc(table.name.toUpperCase())}</div>${rows}`;
  cells.push(`<mxCell id="table-${table.name}" value="${esc(value)}" style="rounded=0;whiteSpace=wrap;html=1;fillColor=#f8fafc;strokeColor=#1f4e78;strokeWidth=2;align=left;verticalAlign=top;fontSize=12;spacing=0;" vertex="1" parent="1"><mxGeometry x="${table.x}" y="${table.y}" width="440" height="${table.height}" as="geometry"/></mxCell>`);
}

for (const child of Object.values(tables)) {
  for (const fk of child.fks) {
    const parent = tables[fk.parent];
    const column = child.columns.find((c) => c.name === fk.column);
    const childMaxOne = child.unique.has(fk.column)
      || (child.primary.size === 1 && child.primary.has(fk.column));
    const startArrow = childMaxOne ? 'ERone' : 'ERmany';
    const endArrow = 'ERone';
    const label = `${fk.column} → ${fk.parentColumn}`;
    cells.push(`<mxCell id="rel-${seq++}" value="${esc(label)}" style="edgeStyle=orthogonalEdgeStyle;rounded=0;orthogonalLoop=1;jettySize=auto;html=1;startArrow=${startArrow};startFill=0;endArrow=${endArrow};endFill=0;strokeColor=#475569;strokeWidth=2;fontSize=11;labelBackgroundColor=#ffffff;" edge="1" parent="1" source="table-${child.name}" target="table-${parent.name}"><mxGeometry relative="1" as="geometry"/></mxCell>`);
  }
}

cells.push(`<mxCell id="title" value="DER lógico relacional — Sistema de Gestión Villafañe Wifi" style="text;html=1;strokeColor=none;fillColor=none;align=center;verticalAlign=middle;fontStyle=1;fontSize=25;" vertex="1" parent="1"><mxGeometry x="650" y="10" width="1800" height="50" as="geometry"/></mxCell>`);
cells.push(`<mxCell id="legend" value="PK: clave primaria · FK: clave foránea · UQ: único · NN: obligatorio · NULL: opcional · Los atributos compuestos fueron aplanados y CLIENTE_TELEFONO proviene del atributo multivaluado." style="shape=note;whiteSpace=wrap;html=1;fillColor=#fff7ed;strokeColor=#d97706;fontSize=13;align=left;spacing=10;" vertex="1" parent="1"><mxGeometry x="700" y="2280" width="1700" height="100" as="geometry"/></mxCell>`);

const drawio = `<mxfile host="app.diagrams.net" modified="2026-08-27T12:00:00.000Z" agent="Codex" version="24.7.17" type="device"><diagram id="logical" name="Modelo lógico relacional"><mxGraphModel dx="${pageWidth}" dy="${pageHeight}" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="${pageWidth}" pageHeight="${pageHeight}" math="0" shadow="0"><root>${cells.join('')}</root></mxGraphModel></diagram></mxfile>`;

const drawioPath = path.join(baseDir, 'DER_Logico_Villafane_Wifi_V2.drawio');
const xmlPath = path.join(baseDir, 'DER_Logico_Villafane_Wifi_V2.xml');
fs.writeFileSync(drawioPath, drawio, 'utf8');
fs.writeFileSync(xmlPath, drawio, 'utf8');

const svg = [];
svg.push(`<svg xmlns="http://www.w3.org/2000/svg" width="${pageWidth}" height="${pageHeight}" viewBox="0 0 ${pageWidth} ${pageHeight}">`);
svg.push('<rect width="100%" height="100%" fill="#ffffff"/>');
svg.push('<style>text{font-family:Arial,sans-serif;fill:#1e293b}.rel{stroke:#64748b;stroke-width:2;fill:none}.box{fill:#f8fafc;stroke:#1f4e78;stroke-width:2}.head{fill:#1f4e78}</style>');
svg.push(`<text x="${pageWidth / 2}" y="42" text-anchor="middle" font-size="28" font-weight="700">DER lógico relacional — Sistema de Gestión Villafañe Wifi</text>`);

for (const child of Object.values(tables)) {
  for (const fk of child.fks) {
    const parent = tables[fk.parent];
    const sx = child.x + 220, sy = child.y + child.height / 2;
    const tx = parent.x + 220, ty = parent.y + parent.height / 2;
    svg.push(`<line class="rel" x1="${sx}" y1="${sy}" x2="${tx}" y2="${ty}"/>`);
    const column = child.columns.find((c) => c.name === fk.column);
    const isSpecialization = fk.parent === 'usuario' && ['empleado', 'administrador'].includes(child.name);
    const childSide = child.unique.has(fk.column)
      || (child.primary.size === 1 && child.primary.has(fk.column)) ? '1' : 'N';
    const relationLabel = isSpecialization ? 'ES' : `${childSide} : 1`;
    svg.push(`<text x="${(sx + tx) / 2}" y="${(sy + ty) / 2 - 7}" text-anchor="middle" font-size="12" font-weight="700">${esc(relationLabel)}</text>`);
  }
}

for (const table of Object.values(tables)) {
  svg.push(`<rect class="box" x="${table.x}" y="${table.y}" width="440" height="${table.height}"/>`);
  svg.push(`<rect class="head" x="${table.x}" y="${table.y}" width="440" height="44"/>`);
  svg.push(`<text x="${table.x + 220}" y="${table.y + 29}" text-anchor="middle" font-size="17" font-weight="700" fill="#ffffff" style="fill:#ffffff">${esc(table.name.toUpperCase())}</text>`);
  table.columns.forEach((c, index) => {
    const marks = [c.pk ? 'PK' : '', c.fk ? 'FK' : '', c.uq ? 'UQ' : ''].filter(Boolean).join(',') || '·';
    const y = table.y + 65 + index * 27;
    svg.push(`<text x="${table.x + 12}" y="${y}" font-size="12" font-weight="700">${esc(marks)}</text>`);
    svg.push(`<text x="${table.x + 70}" y="${y}" font-size="12">${esc(c.name)}</text>`);
    svg.push(`<text x="${table.x + 290}" y="${y}" font-size="11">${esc(c.type)}</text>`);
    svg.push(`<text x="${table.x + 410}" y="${y}" text-anchor="end" font-size="10" fill="#64748b" style="fill:#64748b">${c.nullable ? 'NULL' : 'NN'}</text>`);
  });
}

svg.push('<rect x="650" y="2280" width="1800" height="90" fill="#fff7ed" stroke="#d97706" stroke-width="2"/>');
svg.push('<text x="1550" y="2315" text-anchor="middle" font-size="15">PK: primaria · FK: foránea · UQ: único · NN: obligatorio · NULL: opcional</text>');
svg.push('<text x="1550" y="2345" text-anchor="middle" font-size="15">Los atributos compuestos fueron aplanados; CLIENTE_TELEFONO transforma el atributo multivaluado.</text>');
svg.push('</svg>');

const svgPath = path.join(baseDir, 'DER_Logico_Villafane_Wifi_V2.svg');
fs.writeFileSync(svgPath, svg.join(''), 'utf8');

console.log(`tables=${Object.keys(tables).length}`);
console.log(`foreign_keys=${Object.values(tables).reduce((sum, t) => sum + t.fks.length, 0)}`);
console.log(drawioPath);
console.log(xmlPath);
console.log(svgPath);
