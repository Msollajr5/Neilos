import openpyxl

wb = openpyxl.load_workbook(r"c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx")
ws = wb["L2 SOF"]

for r in range(1, ws.max_row + 1):
    for c in range(1, ws.max_column + 1):
        v = ws.cell(row=r, column=c).value
        if isinstance(v, str):
            if '\t' in v or '\r' in v:
                print(f"Cell {openpyxl.utils.get_column_letter(c)}{r}: has special chars: {repr(v)}")
