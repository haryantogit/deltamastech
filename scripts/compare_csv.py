import csv

def read_csv(path, sku_col_index, name_col_index):
    data = {}
    with open(path, mode='r', encoding='utf-8') as f:
        reader = csv.reader(f)
        header = next(reader)
        for row in reader:
            if not row: continue
            sku = row[sku_col_index].strip()
            name = row[name_col_index].strip()
            if sku.startswith('FA/'):
                data[sku] = name
    return data

product_data = read_csv(r'd:\Program Receh\kledo\data-baru\produk_17-Feb-2026_halaman-1.csv', 1, 0)
asset_data = read_csv(r'd:\Program Receh\kledo\data-baru\aset-tetap_17-Feb-2026_halaman-1.csv', 1, 0)

print("=== SKUs present in both files (Conflicts/Matches) ===")
common_skus = set(product_data.keys()) & set(asset_data.keys())
for sku in sorted(common_skus):
    p_name = product_data[sku]
    a_name = asset_data[sku]
    status = "MATCH" if p_name == a_name else "CONFLICT"
    print(f"{sku}: Produk='{p_name}', Aset='{a_name}' -> {status}")

print("\n=== SKUs only in Produk CSV ===")
only_p = set(product_data.keys()) - set(asset_data.keys())
for sku in sorted(only_p):
    print(f"{sku}: {product_data[sku]}")

print("\n=== SKUs only in Aset Tetap CSV ===")
only_a = set(asset_data.keys()) - set(product_data.keys())
for sku in sorted(only_a):
    print(f"{sku}: {asset_data[sku]}")
