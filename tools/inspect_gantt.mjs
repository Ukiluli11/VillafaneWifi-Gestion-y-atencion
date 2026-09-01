import fs from "node:fs/promises";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const inputPath = "C:/Users/Ulises/Downloads/gantt_villafane_wifi_v2.xlsx";
const outDir = "C:/Users/Ulises/OneDrive/Desktop/Seminario Integracion - Sistema Villafañe/qa/gantt_before";
await fs.mkdir(outDir, { recursive: true });

const input = await FileBlob.load(inputPath);
const workbook = await SpreadsheetFile.importXlsx(input);

console.log((await workbook.inspect({
  kind: "sheet,match",
  searchTerm: "RF-13|RF-12|transfer",
  options: { useRegex: true, maxResults: 50 },
  maxChars: 8000,
})).ndjson);

for (const sheet of workbook.worksheets.items) {
  const preview = await workbook.render({ sheetName: sheet.name, autoCrop: "all", scale: 1, format: "png" });
  await fs.writeFile(`${outDir}/${sheet.name.replace(/[^a-z0-9_-]+/gi, "_")}.png`, new Uint8Array(await preview.arrayBuffer()));
  console.log(`RENDERED ${sheet.name}`);
}
