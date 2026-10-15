import openpyxl

wb = openpyxl.load_workbook(r"c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx")
ws = wb["L2 SOF"]
c = ws['B2']
print("B2 top border color type:", c.border.top.color.type)
print("B2 top border color theme:", c.border.top.color.theme)
print("B2 top border color tint:", c.border.top.color.tint)
print("B2 top border color rgb:", c.border.top.color.rgb)
