# ==============================================================================
# Script: generate_enterprise_sof_pdf.ps1
# Generates the exact 1-page A4 Landscape PDF from "SOF -Template  for Enterprise Clients.xlsx"
# Worksheet: "L2 SOF"
# Output: "SOF_Savanna_Business_Enterprise.pdf"
# ==============================================================================

$baseDir = Split-Path -Parent $PSScriptRoot
$xlsxFile = Join-Path $baseDir "SOF -Template  for Enterprise Clients.xlsx"
$pdfFile = Join-Path $baseDir "SOF_Savanna_Business_Enterprise.pdf"

if (-not (Test-Path $xlsxFile)) {
    Write-Error "Source template not found: $xlsxFile"
    exit 1
}

try {
    $excel = New-Object -ComObject Excel.Application
    $excel.Visible = $false
    $excel.DisplayAlerts = $false
    
    $wb = $excel.Workbooks.Open($xlsxFile)
    $ws = $wb.Sheets.Item("L2 SOF")
    
    # Clean trailing tabs from B2 title to ensure clean typography without box glyph
    $b2Val = $ws.Range("B2").Value2
    if ($b2Val) {
        $ws.Range("B2").Value2 = $b2Val.TrimEnd("`t", " ")
    }
    
    # Exact Page Setup matching Excel layout:
    # 9 = xlPaperA4, 2 = xlLandscape
    $ws.PageSetup.PaperSize = 9
    $ws.PageSetup.Orientation = 2
    $ws.PageSetup.Zoom = $false
    $ws.PageSetup.FitToPagesWide = 1
    $ws.PageSetup.FitToPagesTall = 1
    
    # Margins and Centering
    $ws.PageSetup.LeftMargin = $excel.InchesToPoints(0.25)
    $ws.PageSetup.RightMargin = $excel.InchesToPoints(0.25)
    $ws.PageSetup.TopMargin = $excel.InchesToPoints(0.25)
    $ws.PageSetup.BottomMargin = $excel.InchesToPoints(0.25)
    $ws.PageSetup.CenterHorizontally = $true
    $ws.PageSetup.CenterVertically = $false
    
    # Exact Print Area B2:O49
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
    
    Write-Host "SUCCESS: Generated print-ready PDF -> $pdfFile"
} catch {
    Write-Error "Failed to generate PDF: $($_.Exception.Message)"
    exit 1
}
