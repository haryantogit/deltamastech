# Analisis Tabel Database Tidak Terpakai

Berdasarkan analisis pada struktur database dan kode program, ditemukan beberapa tabel dan model yang terindikasi tidak digunakan (unused) atau redundan.

## 1. Tabel Redundan (Indonesian Name)
Tabel-tabel ini kemungkinan besar adalah sisa dari versi lama atau percobaan lokalisasi yang tidak dilanjutkan. Semuanya memiliki jumlah baris **0 (kosong)** dan tidak direferensikan dalam `app/`.

| Nama Tabel | Model Terkait | Status |
| :--- | :--- | :--- |
| `penawaran_belis` | `PenawaranBeli` | Tidak Terpakai |
| `penawaran_juals` | (Internal) | Tidak Terpakai |
| `pengiriman_belis` | `PengirimanBeli` | Tidak Terpakai |
| `pengiriman_juals` | `PengirimanJual` | Tidak Terpakai |
| `pesanan_belis` | `PesananBeli` | Tidak Terpakai |
| `pesanan_juals` | `PesananJual` | Tidak Terpakai |

> [!TIP]
> Tabel-tabel di atas dapat dihapus jika Anda sudah beralih menggunakan penamaan bahasa Inggris secara konsisten.

## 2. Redundansi Tabel Bahasa Inggris
Ditemukan duplikasi fungsi antara tabel `purchase_quotes` dan `purchase_quotations`.

| Nama Tabel | Model Terkait | Keterangan |
| :--- | :--- | :--- |
| `purchase_quotes` | `PurchaseQuote` | **Aktif**: Digunakan di `PembelianPage` dan `ImportQuotations`. |
| `purchase_quotations`| `PurchaseQuotation`| **Orphaned**: Meskipun modelnya lebih lengkap, namun navigasi di Filament dimatikan dan tidak dipanggil di halaman utama. |

## 3. Kesimpulan & Rekomendasi

### Rekomendasi Penghapusan:
- **Tabel Indonesian**: `penawaran_belis`, `penawaran_juals`, `pengiriman_belis`, `pengiriman_juals`, `pesanan_belis`, `pesanan_juals`.
- **Tabel Redundan**: `purchase_quotations` & `purchase_quotation_items` (karena sistem menggunakan `purchase_quotes`).
- **Model Redundan**: `App\Models\PenawaranBeli`, `App\Models\PengirimanBeli`, `App\Models\PengirimanJual`, `App\Models\PesananBeli`, `App\Models\PesananJual`, `App\Models\PurchaseQuotation`.
- **Resource Redundan**: `PurchaseQuotationResource` (Model ini bisa digabung ke `PurchaseQuoteResource` jika ada fitur yang diperlukan).

### Catatan Penting:
Sebelum menghapus, pastikan:
1. Melakukan backup database (`mysqldump`).
2. Melakukan `grep` ulang untuk memastikan tidak ada query manual (`DB::table(...)`) yang terlewat.
3. Memastikan tidak ada data penting di tabel-tabel tersebut (semua terdeteksi 0 baris saat ini).
