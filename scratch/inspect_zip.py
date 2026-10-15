import zipfile
import xml.etree.ElementTree as ET

xlsx_path = r"c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx"
with zipfile.ZipFile(xlsx_path, 'r') as z:
    print("Files in xlsx:")
    for name in z.namelist():
        print("  ", name)
        
    # Read drawing if any
    for name in z.namelist():
        if 'drawing' in name and name.endswith('.xml'):
            print(f"\n--- {name} ---")
            print(z.read(name).decode('utf-8')[:2000])

    # Read theme
    if 'xl/theme/theme1.xml' in z.namelist():
        print("\n--- Theme Colors ---")
        theme_xml = z.read('xl/theme/theme1.xml')
        root = ET.fromstring(theme_xml)
        for elem in root.iter():
            if elem.tag.endswith('srgbClr'):
                print(elem.tag, elem.attrib)
            elif elem.tag.endswith('sysClr'):
                print(elem.tag, elem.attrib)
