import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "C:/Users/Ulises/Downloads/gantt_villafane_wifi_v2.xlsx";
const outputDir = "C:/Users/Ulises/OneDrive/Desktop/Seminario Integracion - Sistema Villafañe/outputs/actualizacion_rf13";
const outputPath = `${outputDir}/gantt_villafane_wifi_v3.xlsx`;
const qaDir = `${outputDir}/qa`;

const replacements = new Map([
  ["Módulo de usuarios y accesos (RF-29/30)", "Módulo de usuarios y accesos (RF-28/29)"],
  ["Escalado y transferencia de chat (RF-12/13)", "Escalado de conversación a un empleado (RF-12)"],
  ["Conciliación de pagos + OCR (RF-14 a 20)", "Conciliación de pagos + OCR (RF-13 a 19)"],
  ["Soporte técnico / tickets (RF-21 a 24)", "Soporte técnico / tickets (RF-20 a 23)"],
  ["Reportes y dashboard (RF-25 a 28)", "Reportes y dashboard (RF-24 a 27)"],
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

if (changes.length !== 10) {
  throw new Error(`Se esperaban 10 cambios (5 en cada hoja), pero se encontraron ${changes.length}.`);
}

const remaining = await workbook.inspect({
  kind: "match",
  searchTerm: "transferencia de chat|RF-12/13|RF-29/30|RF-14 a 20|RF-21 a 24|RF-25 a 28",
  options: { useRegex: true, maxResults: 50 },
  maxChars: 5000,
});
console.log("CHANGES", JSON.stringify(changes, null, 2));
console.log("OLD_REFERENCES", remaining.ndjson);

for (const sheet of workbook.worksheets.items) {
  const preview = await workbook.render({ sheetName: sheet.name, autoCrop: "all", scale: 1, format: "png" });
  await fs.writeFile(`${qaDir}/${sheet.name.replace(/[^a-z0-9_-]+/gi, "_")}.png`, new Uint8Array(await preview.arrayBuffer()));
}

await fs.mkdir(outputDir, { recursive: true });
const output = await SpreadsheetFile.exportXlsx(workbook);
await output.save(outputPath);
console.log(`OUTPUT ${outputPath}`);
