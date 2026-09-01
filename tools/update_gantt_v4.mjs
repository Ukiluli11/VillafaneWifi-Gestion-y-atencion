import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "C:/Users/Ulises/OneDrive/Desktop/Seminario Integracion - Sistema Villafañe/outputs/actualizacion_rf13/gantt_villafane_wifi_v3.xlsx";
const outputDir = "C:/Users/Ulises/OneDrive/Desktop/Seminario Integracion - Sistema Villafañe/outputs/revision_modelo_v4";
const outputPath = `${outputDir}/gantt_villafane_wifi_v4.xlsx`;
const qaDir = `${outputDir}/qa_gantt`;

const replacements = new Map([
  ["Módulo de usuarios y accesos (RF-28/29)", "Módulo de usuarios y accesos (RF-29/30)"],
  ["Bot conversacional + reconocimiento IA (RF-08)", "Bot conversacional, IA e historial de mensajes (RF-08/13)"],
  ["Escalado de conversación a un empleado (RF-12)", "Escalado de conversación a usuario interno (RF-12)"],
  ["Conciliación de pagos + OCR (RF-13 a 19)", "Conciliación de pagos + OCR (RF-14 a 20)"],
  ["Soporte técnico / tickets (RF-20 a 23)", "Soporte técnico / tickets (RF-21 a 24)"],
  ["Reportes y dashboard (RF-24 a 27)", "Reportes y dashboard (RF-25 a 28)"],
]);

await fs.mkdir(qaDir, { recursive: true });
const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(inputPath));
const changes = [];

for (const sheet of workbook.worksheets.items) {
  const used = sheet.getUsedRange();
  const values = used.values;
  for (let r = 0; r < values.length; r += 1) {
    for (let c = 0; c < values[r].length; c += 1) {
      const value = values[r][c];
      if (typeof value === "string" && replacements.has(value)) {
        const next = replacements.get(value);
        used.getCell(r, c).values = [[next]];
        changes.push({ sheet: sheet.name, from: value, to: next });
      }
    }
  }
}

if (changes.length !== 12) {
  throw new Error(`Se esperaban 12 cambios (6 por hoja), pero se encontraron ${changes.length}.`);
}

console.log("CHANGES", JSON.stringify(changes, null, 2));
console.log((await workbook.inspect({
  kind: "match",
  searchTerm: "RF-28/29|RF-08\\)|a un empleado|RF-13 a 19|RF-20 a 23|RF-24 a 27",
  options: { useRegex: true, maxResults: 50 },
  maxChars: 4000,
})).ndjson);

for (const sheet of workbook.worksheets.items) {
  const preview = await workbook.render({ sheetName: sheet.name, autoCrop: "all", scale: 1, format: "png" });
  await fs.writeFile(`${qaDir}/${sheet.name.replace(/[^a-z0-9_-]+/gi, "_")}.png`, new Uint8Array(await preview.arrayBuffer()));
}

await fs.mkdir(outputDir, { recursive: true });
const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);
console.log(`OUTPUT ${outputPath}`);
