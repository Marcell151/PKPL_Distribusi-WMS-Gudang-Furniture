import xlsxwriter
import os

directory = r"c:\xampp\htdocs\wms_funitur\FPA"
if not os.path.exists(directory):
    os.makedirs(directory)

workbook = xlsxwriter.Workbook(os.path.join(directory, 'FPA_WMS_Furni_Indo.xlsx'))

header_format = workbook.add_format({
    'bold': True,
    'text_wrap': True,
    'valign': 'top',
    'fg_color': '#1E3A8A', # Navy blue
    'font_color': 'white',
    'border': 1
})
cell_format = workbook.add_format({'border': 1})
money_format = workbook.add_format({'border': 1, 'num_format': 'Rp#,##0'})

def create_standard_sheet(name, cols, num_rows=20):
    worksheet = workbook.add_worksheet(name)
    for col_num, col_name in enumerate(cols):
        worksheet.write(0, col_num, col_name, header_format)
        worksheet.set_column(col_num, col_num, 25)
    for row in range(1, num_rows + 1):
        for col_num in range(len(cols)):
            worksheet.write(row, col_num, "", cell_format)
    
    last_col = len(cols) - 1
    sum_row = num_rows + 1
    worksheet.write(sum_row, last_col - 1, "Total Poin", header_format)
    col_letter = chr(ord('A') + last_col)
    worksheet.write_formula(sum_row, last_col, f"=SUM({col_letter}2:{col_letter}{num_rows+1})", cell_format)
    return worksheet

ws1 = create_standard_sheet("1. Data Master (ILF)", ["Nama Tabel/Entitas", "Tipe Record (RET)", "Elemen Data (DET)", "Kompleksitas (Mudah/Sedang/Sulit)", "Poin (7, 10, atau 15)"])
ws2 = create_standard_sheet("2. Data Eksternal (EIF)", ["Nama Referensi", "Tipe Record (RET)", "Elemen Data (DET)", "Kompleksitas (Mudah/Sedang/Sulit)", "Poin (5, 7, atau 10)"])
ws3 = create_standard_sheet("3. Form Input (EI)", ["Nama Form/Layar", "Referensi Tabel (FTR)", "Elemen Data (DET)", "Kompleksitas (Mudah/Sedang/Sulit)", "Poin (3, 4, atau 6)"])
ws4 = create_standard_sheet("4. Laporan & Cetak (EO)", ["Nama Laporan/Dokumen", "Referensi Tabel (FTR)", "Elemen Data (DET)", "Kompleksitas (Mudah/Sedang/Sulit)", "Poin (4, 5, atau 7)"])
ws5 = create_standard_sheet("5. Tampil Data (EQ)", ["Nama Layar Inquiry", "Referensi Tabel (FTR)", "Elemen Data (DET)", "Kompleksitas (Mudah/Sedang/Sulit)", "Poin (3, 4, atau 6)"])

# Sheet 6. VAF
ws6 = workbook.add_worksheet("6. Faktor Penyesuaian (VAF)")
vaf_headers = ["No", "Deskripsi Karakteristik", "Nilai Pengaruh (0=Tidak Ada s/d 5=Kuat)"]
for col_num, col_name in enumerate(vaf_headers):
    ws6.write(0, col_num, col_name, header_format)
ws6.set_column(1, 1, 45)
ws6.set_column(2, 2, 35)

characteristics = [
    "Data Communications (Komunikasi Data)",
    "Distributed Data Processing (Pemrosesan Data Terdistribusi)",
    "Performance (Performa Kinerja)",
    "Heavily Used Configuration (Konfigurasi Sering Digunakan)",
    "Transaction Rate (Tingkat Transaksi)",
    "Online Data Entry (Entri Data Online)",
    "End-User Efficiency (Efisiensi Pengguna Akhir)",
    "Online Update (Pembaruan Online)",
    "Complex Processing (Pemrosesan Kompleks)",
    "Reusability (Dapat Digunakan Kembali)",
    "Installation Ease (Kemudahan Instalasi)",
    "Operational Ease (Kemudahan Operasional)",
    "Multiple Sites (Beragam Lokasi)",
    "Facilitate Change (Memfasilitasi Perubahan)"
]

for row, char in enumerate(characteristics, start=1):
    ws6.write(row, 0, row, cell_format)
    ws6.write(row, 1, char, cell_format)
    ws6.write(row, 2, 0, cell_format)

row_tdi = len(characteristics) + 1
ws6.write(row_tdi, 1, "Total Degree of Influence (TDI)", header_format)
ws6.write_formula(row_tdi, 2, f"=SUM(C2:C15)", cell_format)

row_vaf = row_tdi + 1
ws6.write(row_vaf, 1, "Value Adjustment Factor (VAF)", header_format)
ws6.write_formula(row_vaf, 2, f"=(C{row_tdi+1}*0.01)+0.65", cell_format)

# Sheet 7. Summary
ws7 = workbook.add_worksheet("7. Ringkasan & Harga")
ws7.set_column(0, 0, 40)
ws7.set_column(1, 1, 20)

ws7.write(0, 0, "Komponen Perhitungan", header_format)
ws7.write(0, 1, "Nilai / Formula", header_format)

ws7.write(1, 0, "Total UFP (Unadjusted Function Point)", cell_format)
ws7.write_formula(1, 1, "='1. Data Master (ILF)'!E22 + '2. Data Eksternal (EIF)'!E22 + '3. Form Input (EI)'!E22 + '4. Laporan & Cetak (EO)'!E22 + '5. Tampil Data (EQ)'!E22", cell_format)

ws7.write(2, 0, "Value Adjustment Factor (VAF)", cell_format)
ws7.write_formula(2, 1, "='6. Faktor Penyesuaian (VAF)'!C17", cell_format)

ws7.write(3, 0, "Final Function Point (FP)", header_format)
ws7.write_formula(3, 1, "=B2 * B3", cell_format)

ws7.write(5, 0, "--- ESTIMASI HARGA ---", header_format)
ws7.write(5, 1, "", header_format)

ws7.write(6, 0, "Jam Kerja per FP (Misal: 8 jam)", cell_format)
ws7.write(6, 1, 8, cell_format)

ws7.write(7, 0, "Total Jam Kerja (FP * Jam Kerja per FP)", cell_format)
ws7.write_formula(7, 1, "=B4 * B7", cell_format)

ws7.write(8, 0, "Gaji Programmer per Hari (Asumsi 8 Jam/Hari)", cell_format)
ws7.write(8, 1, 500000, money_format)

ws7.write(9, 0, "Biaya Akomodasi/Overhead per Hari", cell_format)
ws7.write(9, 1, 100000, money_format)

ws7.write(10, 0, "Total Biaya Per Hari", cell_format)
ws7.write_formula(10, 1, "=B9 + B10", money_format)

ws7.write(11, 0, "HARGA POKOK (Base Price)", header_format)
ws7.write_formula(11, 1, "=(B8/8) * B11", money_format)

workbook.close()
print("Berhasil membuat file Excel FPA_WMS_Furni_Indo.xlsx")
