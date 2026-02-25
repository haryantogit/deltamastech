# Panduan Penggunaan Modul Produksi (Konversi Produk)

Modul Produksi digunakan untuk mencatat proses pengolahan bahan baku menjadi produk jadi.

## 1. Persiapan: Atur Resep (BOM)
Sebelum melakukan produksi, Anda harus mengatur "resep" atau Bill of Materials (BOM) pada produk yang bertipe **Manufaktur**.

1. Buka menu **Master Data > Produk**.
2. Edit atau buat produk baru, pastikan **Jenis Produk** diatur ke **Manufaktur (Produksi)**.
3. Di bagian bawah, akan muncul section **"Produk manufaktur terdiri dari"**.
4. Masukkan bahan-bahan yang dibutuhkan dan jumlahnya untuk menghasilkan **1 unit** produk tersebut.
5. Anda juga bisa memasukkan biaya overhead (listrik, karyawan, dll) di bagian **"Biaya produksi terdiri dari"**.

## 2. Melakukan Konversi Produk (Produksi)
Setelah resep siap, Anda bisa melakukan transaksi produksi di menu **Produksi > Konversi Produk**.

1. Buka menu **Produksi > Konversi Produk**.
2. Klik **Buat Konversi Produk**.
3. Pilih **Produk Hasil** yang ingin diproduksi.
4. Masukkan **Gudang** tempat bahan diambil dan hasil disimpan.
5. Masukkan **Kuantitas Produksi**. Sistem akan otomatis mengisi daftar bahan baku dan biaya berdasarkan resep yang telah Anda atur.
6. Anda masih bisa menyesuaikan jumlah bahan baku atau biaya secara manual pada transaksi ini jika terjadi perbedaan di lapangan.
7. Simpan sebagai **Draft**.

## 3. Menyelesaikan Produksi (Update Stok)
Produksi yang masih berstatus **Draft** belum memotong stok bahan baku maupun menambah stok produk jadi.

1. Buka daftar transaksi **Konversi Produk**.
2. Klik tombol **"Selesaikan Produksi"** (ikon centang hijau) pada transaksi yang dimaksud.
3. **PENTING:** Saat Anda klik tombol ini, sistem akan:
    - Mengurangi stok bahan baku di gudang terpilih.
    - Menambah stok produk jadi di gudang terpilih.
    - Mengupdate status transaksi menjadi **Selesai**.

## 4. Melihat Laporan
Buka menu **Produksi > Laporan Produksi** untuk melihat riwayat konversi produk yang telah selesai beserta rincian biaya HPP yang dihasilkan.
