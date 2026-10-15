import openpyxl

wb = openpyxl.load_workbook(r"c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx")
ws = wb["L2 SOF"]

print("--- Top border (Row 2, Cols B to O) ---")
for c in range(2, 16):
    cell = ws.cell(row=2, column=c)
    top_s = cell.border.top.style if cell.border and cell.border.top else None
    print(f"Col {openpyxl.utils.get_column_letter(c)}2: top={top_s}")

print("\n--- Bottom border (Row 49, Cols B to O) ---")
for c in range(2, 16):
    cell = ws.cell(row=49, column=c)
    bot_s = cell.border.bottom.style if cell.border and cell.border.bottom else None
    print(f"Col {openpyxl.utils.get_column_letter(c)}49: bottom={bot_s}")

print("\n--- Left border (Col B, Rows 2 to 49) ---")
for r in range(2, 50):
    cell = ws.cell(row=r, column=2)
    left_s = cell.border.left.style if cell.border and cell.border.left else None
    if left_s != 'medium':
        print(f"Row B{r}: left={left_s}")

print("\n--- Right border (Col O, Rows 2 to 49) ---")
for r in range(2, 50):
    cell = ws.cell(row=r, column=15)
    right_s = cell.border.right.style if cell.border and cell.border.right else None
    if right_s != 'medium':
        print(f"Row O{r}: right={right_s}")
