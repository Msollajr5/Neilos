import openpyxl
import zipfile
import xml.etree.ElementTree as ET

# Parse theme colors from theme1.xml
xlsx_path = r"c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx"

with zipfile.ZipFile(xlsx_path, 'r') as z:
    theme_xml = z.read('xl/theme/theme1.xml')
    styles_xml = z.read('xl/styles.xml')

root_theme = ET.fromstring(theme_xml)
ns = {'a': 'http://schemas.openxmlformats.org/drawingml/2006/main'}

# Theme color scheme
clrScheme = root_theme.find('.//a:clrScheme', ns)
theme_colors = []
for child in clrScheme:
    srgb = child.find('a:srgbClr', ns)
    sys = child.find('a:sysClr', ns)
    if srgb is not None:
        theme_colors.append(srgb.attrib['val'])
    elif sys is not None:
        theme_colors.append(sys.attrib.get('lastClr', '000000'))
    else:
        theme_colors.append('000000')

print("Theme colors (0 to 11):")
for i, c in enumerate(theme_colors):
    print(f"  Theme {i}: #{c}")

# Theme mapping in Excel:
# 0: lt1 (sysClr window) -> usually FFFFFF
# 1: dk1 (sysClr windowText) -> usually 000000
# 2: lt2 (srgbClr)
# 3: dk2 (srgbClr)
# 4: accent1
# 5: accent2
# 6: accent3
# 7: accent4
# 8: accent5
# 9: accent6
# 10: hlink
# 11: folHlink
