import openpyxl

wb_path = r"c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx"
wb = openpyxl.load_workbook(wb_path, data_only=False)
ws = wb["L2 SOF"]

import sys

out_lines = []
out_lines.append(f"Max row: {ws.max_row}, Max col: {ws.max_column}")
out_lines.append("\n--- NON-EMPTY ROWS & CONTENT ---")
for r in range(1, ws.max_row + 1):
    row_vals = []
    has_val = False
    for c in range(1, ws.max_column + 1):
        v = ws.cell(row=r, column=c).value
        if v is not None:
            has_val = True
            row_vals.append(f"C{c}({openpyxl.utils.get_column_letter(c)}): {repr(v)}")
    if has_val:
        rd = ws.row_dimensions.get(r)
        h = rd.height if rd else 'auto'
        out_lines.append(f"Row {r:2d} (h={h}): " + " | ".join(row_vals))

out_lines.append("\n--- COLUMN WIDTHS ---")
for c in range(1, ws.max_column + 1):
    col_letter = openpyxl.utils.get_column_letter(c)
    cd = ws.column_dimensions.get(col_letter)
    out_lines.append(f"Col {c:2d} ({col_letter}): width = {cd.width if cd else 'default'}")

out_lines.append("\n--- MERGED RANGES ---")
merged = sorted([str(m) for m in ws.merged_cells.ranges])
for m in merged:
    out_lines.append(m)

with open(r"c:\xampp\htdocs\Neilos\scratch\l2_sof_dump.txt", "w", encoding="utf-8") as f:
    f.write("\n".join(out_lines))

print("Dumped to scratch/l2_sof_dump.txt")
