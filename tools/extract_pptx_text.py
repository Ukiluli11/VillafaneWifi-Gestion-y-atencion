from pathlib import Path
from zipfile import ZipFile
from xml.etree import ElementTree as ET
import re
import sys

sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ROOT = Path(r"C:\Users\Ulises\OneDrive\Desktop\Seminario Integracion - Sistema Villafañe\qa\material_bd\Base de datos power")
NS = {"a": "http://schemas.openxmlformats.org/drawingml/2006/main"}

for pptx in sorted(ROOT.glob("*.pptx")):
    print(f"\n===== {pptx.name} =====")
    with ZipFile(pptx) as zf:
        slides = sorted(
            (n for n in zf.namelist() if re.fullmatch(r"ppt/slides/slide\d+\.xml", n)),
            key=lambda n: int(re.search(r"(\d+)", Path(n).stem).group(1)),
        )
        print(f"SLIDES={len(slides)}")
        for index, slide_name in enumerate(slides, 1):
            root = ET.fromstring(zf.read(slide_name))
            texts = [t.text.strip() for t in root.findall(".//a:t", NS) if t.text and t.text.strip()]
            print(f"SLIDE {index}: {' | '.join(texts)}")
