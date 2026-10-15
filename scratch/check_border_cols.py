import openpyxl

wb = openpyxl.load_workbook(r"c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx")
ws = wb["L2 SOF"]

for c in range(1, 20):
    col_let = openpyxl.utils.get_column_letter(c)
    for r in range(1, 6):
        cell = ws.cell(row=r, column=c)
        if cell.border and (cell.border.top.style or cell.border.right.style or cell.border.left.style or cell.border.bottom.style):
            print(f"{col_let}{r}: top={cell.border.top.style} right={cell.border.right.style} left={cell.border.left.style} bottom={cell.border.bottom.style} color={cell.border.top.color.rgb if cell.border.top and cell.border.top.color else (cell.border.top.color.theme if cell.border.top and cell.border.top.color else None)}")
