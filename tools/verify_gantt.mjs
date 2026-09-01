import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const path = "C:/Users/Ulises/OneDrive/Desktop/Seminario Integracion - Sistema Villafañe/outputs/revision_modelo_v4/gantt_villafane_wifi_v4.xlsx";
const workbook = await SpreadsheetFile.importXlsx(await FileBlob.load(path));

console.log((await workbook.inspect({
  kind: "table",
  range: "Datos del proyecto!A15:F41",
  include: "values,formulas",
  tableMaxRows: 30,
  tableMaxCols: 6,
  maxChars: 12000,
})).ndjson);

console.log((await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
  options: { useRegex: true, maxResults: 300 },
  maxChars: 3000,
})).ndjson);
