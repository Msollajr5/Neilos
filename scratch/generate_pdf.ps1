$excelPath = "C:\Program Files\Microsoft Office\root\Office16\EXCEL.EXE"
$xlsxFile = "c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx"
$pdfFile = "c:\xampp\htdocs\Neilos\SOF_Savanna_Business_Enterprise.pdf"

try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    
    $wb = $excel.Workbooks.Open($xlsxFile)
    $ws = $wb.Sheets.Item("L2 SOF")
    
    # Clean trailing tabs from B2 title to avoid broken glyph box
    $b2Val = $ws.Range("B2").Value2
    if ($b2Val) {
        $ws.Range("B2").Value2 = $b2Val.TrimEnd("`t", " ")
    }
    
    # 9 = xlPaperA4, 2 = xlLandscape
    $ws.PageSetup.PaperSize = 9
    $ws.PageSetup.Orientation = 2
    $ws.PageSetup.Zoom = $false
    $ws.PageSetup.FitToPagesWide = 1
    $ws.PageSetup.FitToPagesTall = 1
    
    # Margins
    $ws.PageSetup.LeftMargin = $excel.InchesToPoints(0.25)
    $ws.PageSetup.RightMargin = $excel.InchesToPoints(0.25)
    $ws.PageSetup.TopMargin = $excel.InchesToPoints(0.25)
    $ws.PageSetup.BottomMargin = $excel.InchesToPoints(0.25)
    $ws.PageSetup.CenterHorizontally = $true
    $ws.PageSetup.CenterVertically = $false
    
    # Print Area B2:O49
    $ws.PageSetup.PrintArea = "B2:O49"
    
    # 0 = xlTypePDF, 0 = xlQualityStandard
    $ws.ExportAsFixedFormat(0, $pdfFile, 0, $true, $false)
    
    $wb.Close($false)
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($ws) | Out-Null
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($wb) | Out-Null
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
    [System.GC]::Collect()
    [System.GC]::WaitForPendingFinalizers()
    
    Write-Host "SUCCESS: Generated $pdfFile"
} catch {
    Write-Host "ERROR: $($_.Exception.Message)"
}
