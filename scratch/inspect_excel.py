import openpyxl
from openpyxl.utils import get_column_letter
import json
import os

wb_path = r"c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx"
wb = openpyxl.load_workbook(wb_path, data_only=False)

print(f"Sheet names: {wb.sheetnames}")

for sheetname in wb.sheetnames:
    ws = wb[sheetname]
    print(f"\n--- Sheet: {sheetname} ---")
    print(f"Max row: {ws.max_row}, Max col: {ws.max_column}")
    print(f"Page orientation: {ws.page_setup.orientation}")
    print(f"Paper size: {ws.page_setup.paperSize}")
    print(f"Print area: {ws.print_area}")
    print(f"Margins: left={ws.page_margins.left}, right={ws.page_margins.right}, top={ws.page_margins.top}, bottom={ws.page_margins.bottom}")

ws = wb["L2 SOF"]

info = {
    "sheet_name": "L2 SOF",
    "max_row": ws.max_row,
    "max_column": ws.max_column,
    "merged_ranges": [str(m) for m in ws.merged_cells.ranges],
    "column_dimensions": {},
    "row_dimensions": {},
    "cells": []
}

for col_idx in range(1, ws.max_column + 1):
    col_letter = get_column_letter(col_idx)
    cd = ws.column_dimensions.get(col_letter)
    width = cd.width if cd else None
    info["column_dimensions"][col_letter] = width

for row_idx in range(1, ws.max_row + 1):
    rd = ws.row_dimensions.get(row_idx)
    height = rd.height if rd else None
    info["row_dimensions"][row_idx] = height

# Let's inspect each cell that has content or borders or fill
cells_data = []
for r in range(1, ws.max_row + 1):
    row_cells = []
    for c in range(1, ws.max_column + 1):
        cell = ws.cell(row=r, column=c)
        val = cell.value
        
        # Color extraction helper
        def get_color(color_obj):
            if not color_obj:
                return None
            if color_obj.type == 'rgb':
                return color_obj.rgb
            elif color_obj.type == 'theme':
                return f"theme:{color_obj.theme},tint:{color_obj.tint}"
            elif color_obj.type == 'indexed':
                return f"indexed:{color_obj.indexed}"
            return str(color_obj)

        fill_color = None
        if cell.fill and cell.fill.fill_type:
            fill_color = {
                "type": cell.fill.fill_type,
                "fgColor": get_color(cell.fill.fgColor),
                "bgColor": get_color(cell.fill.bgColor)
            }
        
        font_info = None
        if cell.font:
            font_info = {
                "name": cell.font.name,
                "size": cell.font.size,
                "bold": cell.font.bold,
                "italic": cell.font.italic,
                "color": get_color(cell.font.color)
            }
            
        align_info = None
        if cell.alignment:
            align_info = {
                "horizontal": cell.alignment.horizontal,
                "vertical": cell.alignment.vertical,
                "wrap_text": cell.alignment.wrap_text,
                "indent": cell.alignment.indent
            }
            
        border_info = None
        if cell.border:
            border_info = {
                "left": {"style": cell.border.left.style, "color": get_color(cell.border.left.color)} if cell.border.left else None,
                "right": {"style": cell.border.right.style, "color": get_color(cell.border.right.color)} if cell.border.right else None,
                "top": {"style": cell.border.top.style, "color": get_color(cell.border.top.color)} if cell.border.top else None,
                "bottom": {"style": cell.border.bottom.style, "color": get_color(cell.border.bottom.color)} if cell.border.bottom else None,
            }
            
        cell_dict = {
            "coordinate": cell.coordinate,
            "row": r,
            "col": c,
            "value": str(val) if val is not None else None,
            "data_type": cell.data_type,
            "number_format": cell.number_format,
            "font": font_info,
            "fill": fill_color,
            "alignment": align_info,
            "border": border_info
        }
        row_cells.append(cell_dict)
    cells_data.append(row_cells)

info["cells"] = cells_data

# Also check images
images_info = []
if hasattr(ws, '_images'):
    for i, img in enumerate(ws._images):
        images_info.append({
            "index": i,
            "width": getattr(img, 'width', None),
            "height": getattr(img, 'height', None),
            "format": getattr(img, 'format', None)
        })
        # Save image if present
        if hasattr(img, '_data'):
            with open(rf"c:\xampp\htdocs\Neilos\scratch\excel_img_{i}.png", "wb") as img_f:
                img_f.write(img._data())
info["images"] = images_info

with open(r"c:\xampp\htdocs\Neilos\scratch\excel_inspection.json", "w", encoding="utf-8") as f:
    json.dump(info, f, indent=2, ensure_ascii=False)

print("\nSuccessfully inspected and saved to scratch/excel_inspection.json")
