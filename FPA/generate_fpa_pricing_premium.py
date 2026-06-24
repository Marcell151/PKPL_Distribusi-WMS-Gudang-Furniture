import xlsxwriter
import os

directory = r"c:\xampp\htdocs\wms_funitur\FPA"
if not os.path.exists(directory):
    os.makedirs(directory)

workbook = xlsxwriter.Workbook(os.path.join(directory, 'FPA_Pricing_WMS_Furni_Premium.xlsx'))

header_format = workbook.add_format({
    'bold': True,
    'valign': 'top',
    'fg_color': '#1E3A8A', # Navy blue
    'font_color': 'white',
    'border': 1
})
cell_format = workbook.add_format({'border': 1})
cell_center = workbook.add_format({'border': 1, 'align': 'center'})
money_format = workbook.add_format({'border': 1, 'num_format': 'Rp#,##0'})
bold_format = workbook.add_format({'bold': True, 'border': 1})

# Helper function to write data
def write_sheet_data(ws, headers, data_rows):
    for col, h in enumerate(headers):
        ws.write(0, col, h, header_format)
        ws.set_column(col, col, 20)
    for row, row_data in enumerate(data_rows, start=1):
        for col, val in enumerate(row_data):
            if isinstance(val, int):
                ws.write(row, col, val, cell_center)
            else:
                ws.write(row, col, val, cell_format)
    # Add sum formula at the bottom for the FP column (last column)
    last_row = len(data_rows)
    last_col = len(headers) - 1
    ws.write(last_row + 1, last_col - 1, "Total", bold_format)
    col_letter = chr(ord('A') + last_col)
    ws.write_formula(last_row + 1, last_col, f"=SUM({col_letter}2:{col_letter}{last_row+1})", bold_format)

# 1. ILF
ws1 = workbook.add_worksheet("1_FPA_Data_Master (ILF)")
ilf_headers = ["Modul", "Nama Entitas (Tabel)", "DET (Jml Kolom)", "RET", "Kompleksitas", "FP"]
ilf_data = [
    ["Master Data", "Data Barang (tb_furniture)", 6, 1, "Rendah", 7],
    ["Master Data", "Data Supplier (tb_supplier)", 5, 1, "Rendah", 7],
    ["Master Data", "Data Toko (tb_toko)", 5, 1, "Rendah", 7],
    ["Master Data", "Data Lokasi (tb_lokasi)", 4, 1, "Rendah", 7],
    ["Master Data", "Data Gudang (tb_gudang)", 4, 1, "Rendah", 7],
    ["Master Data", "Data Users (tb_users)", 3, 1, "Rendah", 7],
]
write_sheet_data(ws1, ilf_headers, ilf_data)

# 2. EI
ws2 = workbook.add_worksheet("2_FPA_Form_Input (EI)")
ei_headers = ["Modul", "Nama Form (Proses)", "DET (Input)", "FTR (Tabel Terkait)", "Kompleksitas", "FP"]
ei_data = [
    ["Transaksi", "Form Purchase Order (PO)", 10, 3, "Sedang", 4],
    ["Transaksi", "Form Sales Order (SO)", 10, 3, "Sedang", 4],
    ["Logistik", "Form Inbound", 8, 4, "Sedang", 4],
    ["Logistik", "Form Outbound/QC", 7, 3, "Sedang", 4],
    ["Logistik", "Form Lapor Waste", 5, 2, "Sedang", 4],
    ["Logistik", "Form Approval Waste", 4, 2, "Sedang", 4],
    ["Logistik", "Form Transfer Gudang", 6, 3, "Sedang", 4],
    ["Logistik", "Form Stock Opname", 6, 2, "Sedang", 4],
]
write_sheet_data(ws2, ei_headers, ei_data)

# 3. EO
ws3 = workbook.add_worksheet("3_FPA_Laporan_Output (EO)")
eo_headers = ["Modul", "Nama Laporan/Cetak", "DET", "FTR", "Kompleksitas", "FP"]
eo_data = [
    ["Cetak", "Cetak Dokumen PO", 15, 3, "Sedang", 5],
    ["Cetak", "Cetak Surat Jalan SO", 15, 3, "Sedang", 5],
    ["Cetak", "Cetak Nota Selisih", 10, 2, "Sedang", 5],
    ["Laporan", "Laporan Konsolidasi Stok", 12, 4, "Sedang", 5],
    ["Laporan", "Laporan Riwayat Mutasi", 10, 3, "Sedang", 5],
    ["Laporan", "Laporan Barang Waste", 8, 2, "Sedang", 5],
]
write_sheet_data(ws3, eo_headers, eo_data)

# 4. EQ
ws4 = workbook.add_worksheet("4_FPA_Layar_Tampil (EQ)")
eq_headers = ["Modul", "Nama Layar", "DET", "FTR", "Kompleksitas", "FP"]
eq_data = [
    ["Dashboard", "Dashboard Statistik", 10, 4, "Rendah", 3],
    ["Informasi", "Informasi Stok (Read-Only)", 8, 3, "Rendah", 3],
    ["Informasi", "Kartu Stok Detail", 12, 3, "Rendah", 3],
]
write_sheet_data(ws4, eq_headers, eq_data)

# 5. VAF
ws5 = workbook.add_worksheet("5_VAF_Faktor_Sistem")
ws5.write(0, 0, "No", header_format)
ws5.write(0, 1, "Deskripsi Karakteristik", header_format)
ws5.write(0, 2, "Nilai (0-5)", header_format)
ws5.set_column(1, 1, 40)
ws5.set_column(2, 2, 15)

vaf_data = [
    ("Data Communications", 3),
    ("Distributed Data Processing", 2),
    ("Performance", 4),
    ("Heavily Used Configuration", 3),
    ("Transaction Rate", 4),
    ("Online Data Entry", 5),
    ("End-User Efficiency", 4),
    ("Online Update", 5),
    ("Complex Processing", 3),
    ("Reusability", 2),
    ("Installation Ease", 3),
    ("Operational Ease", 4),
    ("Multiple Sites", 4),
    ("Facilitate Change", 3),
]
for r, (desc, val) in enumerate(vaf_data, start=1):
    ws5.write(r, 0, r, cell_center)
    ws5.write(r, 1, desc, cell_format)
    ws5.write(r, 2, val, cell_center)

ws5.write(16, 1, "Total Degree of Influence (TDI)", bold_format)
ws5.write_formula(16, 2, "=SUM(C2:C15)", bold_format)
ws5.write(17, 1, "Value Adjustment Factor (VAF)", bold_format)
ws5.write_formula(17, 2, "=(C17*0.01)+0.65", bold_format)

# 6. Kalkulasi Waktu
ws6 = workbook.add_worksheet("6_Kalkulasi_Waktu (Effort)")
ws6.set_column(0, 0, 30)
ws6.set_column(1, 1, 15)
ws6.write(0, 0, "Komponen", header_format)
ws6.write(0, 1, "Nilai", header_format)

ws6.write(1, 0, "Total UFP (ILF+EI+EO+EQ)", cell_format)
ws6.write_formula(1, 1, "='1_FPA_Data_Master (ILF)'!F8 + '2_FPA_Form_Input (EI)'!F10 + '3_FPA_Laporan_Output (EO)'!F8 + '4_FPA_Layar_Tampil (EQ)'!F5", cell_format)

ws6.write(2, 0, "VAF (Dari Sheet 5)", cell_format)
ws6.write_formula(2, 1, "='5_VAF_Faktor_Sistem'!C18", cell_format)

ws6.write(3, 0, "Total Final FP", bold_format)
ws6.write_formula(3, 1, "=B2*B3", bold_format)

ws6.write(5, 0, "Konversi FP ke Jam Kerja", cell_format)
ws6.write(5, 1, 8, cell_center) # 8 Jam per FP

ws6.write(6, 0, "Total Estimasi Jam Kerja", cell_format)
ws6.write_formula(6, 1, "=B4*B6", cell_center)

ws6.write(7, 0, "Jam Kerja per Hari", cell_format)
ws6.write(7, 1, 8, cell_center)

ws6.write(8, 0, "Total Hari Kerja", bold_format)
ws6.write_formula(8, 1, "=B7/B8", bold_format)

# 7. Biaya SDM & Operasional
ws7 = workbook.add_worksheet("7_Biaya_SDM_Operasional")
ws7.set_column(0, 0, 25)
ws7.set_column(1, 1, 15)
ws7.set_column(2, 2, 20)
ws7.set_column(3, 3, 20)

ws7.write(0, 0, "Biaya SDM", header_format)
ws7.write(0, 1, "Hari Kerja", header_format)
ws7.write(0, 2, "Rate per Hari", header_format)
ws7.write(0, 3, "Total Biaya", header_format)

sdm = [
    ("System Analyst", 500000),
    ("Programmer PHP", 400000),
    ("UI/UX Designer", 350000)
]
for r, (role, rate) in enumerate(sdm, start=1):
    ws7.write(r, 0, role, cell_format)
    ws7.write_formula(r, 1, "='6_Kalkulasi_Waktu (Effort)'!B9", cell_center)
    ws7.write(r, 2, rate, money_format)
    ws7.write_formula(r, 3, f"=B{r+1}*C{r+1}", money_format)

ws7.write(4, 2, "Subtotal SDM", bold_format)
ws7.write_formula(4, 3, "=SUM(D2:D4)", money_format)

ws7.write(6, 0, "Biaya Operasional", header_format)
ws7.write(6, 1, "Bulan/Pcs", header_format)
ws7.write(6, 2, "Harga Satuan", header_format)
ws7.write(6, 3, "Total Biaya", header_format)

ops = [
    ("Server/Hosting (1 thn)", 1, 1500000),
    ("Internet/Bulan", 2, 400000),
    ("Listrik/Bulan", 2, 300000),
    ("Transportasi & Rapat", 5, 100000)
]
for r, (item, qty, price) in enumerate(ops, start=7):
    ws7.write(r, 0, item, cell_format)
    ws7.write(r, 1, qty, cell_center)
    ws7.write(r, 2, price, money_format)
    ws7.write_formula(r, 3, f"=B{r+1}*C{r+1}", money_format)

ws7.write(11, 2, "Subtotal Operasional", bold_format)
ws7.write_formula(11, 3, "=SUM(D8:D11)", money_format)

ws7.write(13, 2, "TOTAL HPP", header_format)
ws7.write_formula(13, 3, "=D5+D12", money_format)

# 8. Pricing
ws8 = workbook.add_worksheet("8_Penawaran_Harga (Pricing)")
ws8.set_column(0, 0, 35)
ws8.set_column(1, 1, 25)

ws8.write(0, 0, "Komponen Penawaran", header_format)
ws8.write(0, 1, "Nominal", header_format)

ws8.write(1, 0, "Harga Pokok Proyek (HPP)", cell_format)
ws8.write_formula(1, 1, "='7_Biaya_SDM_Operasional'!D14", money_format)

ws8.write(2, 0, "Batas Bawah Penawaran (+20% Margin)", bold_format)
ws8.write_formula(2, 1, "=B2*1.2", money_format)

ws8.write(3, 0, "Batas Atas Penawaran (+45% Margin)", bold_format)
ws8.write_formula(3, 1, "=B2*1.45", money_format)

workbook.close()
print("Berhasil membuat file Excel FPA_Pricing_WMS_Furni_Premium.xlsx")
