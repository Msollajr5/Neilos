import openpyxl
from openpyxl.utils import get_column_letter

wb = openpyxl.load_workbook(r"c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx", data_only=True)
ws = wb["L2 SOF"]

for r in range(1, 15):
    print(f"\n--- ROW {r} ---")
    for c in range(1, 16):
        col_let = get_column_letter(c)
        cell = ws.cell(row=r, column=c)
        val = cell.value
        fill = cell.fill.fgColor.rgb if (cell.fill and cell.fill.fgColor and cell.fill.fgColor.type == 'rgb') else (cell.fill.fgColor.theme if (cell.fill and cell.fill.fgColor) else None)
        borders = []
        if cell.border:
            for b_name in ['left', 'right', 'top', 'bottom']:
                b = getattr(cell.border, b_name)
                if b and b.style:
                    borders.append(f"{b_name}:{b.style}")
        b_str = ",".join(borders) if borders else "none"
        if val is not None or fill is not None or borders:
            print(f"  {col_let}{r}: val={repr(val)} fill={fill} font={cell.font.name if cell.font else None} size={cell.font.size if cell.font else None} bold={cell.font.bold if cell.font else None} borders=[{b_str}]")
