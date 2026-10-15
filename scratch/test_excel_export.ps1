$xlsxFile = "c:\xampp\htdocs\Neilos\SOF -Template  for Enterprise Clients.xlsx"
$pdfFile = "c:\xampp\htdocs\Neilos\SOF_Savanna_Business_Enterprise.pdf"

try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    
    $wb = $excel.Workbooks.Open($xlsxFile)
    $ws = $wb.Sheets.Item("L2 SOF")
    
    # xlPaperA4 = 9
    # xlLandscape = 2
    $ws.PageSetup.PaperSize = 9
    $ws.PageSetup.Orientation = 2
    $ws.PageSetup.Zoom = $false
    $ws.PageSetup.FitToPagesWide = 1
    $ws.PageSetup.FitToPagesTall = 1
    
    # Ensure Print Area covers used range: B2:O49
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
    
    Write-Host "SUCCESS: Exported 1-page A4 PDF to $pdfFile"
} catch {
    Write-Host "ERROR: $($_.Exception.Message)"
}
