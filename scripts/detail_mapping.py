import csv

def get_mapping(path):
    mapping = {}
    with open(path, mode='r', encoding='utf-8') as f:
        reader = csv.reader(f)
        next(reader)
        for row in reader:
            if not row: continue
            name = row[0].strip()
            sku = row[1].strip()
            if sku.startswith('FA/'):
                mapping[sku] = name
    return mapping

print("--- PRODUK CSV FA/ ITEMS ---")
p_map = get_mapping(r'd:\Program Receh\kledo\data-baru\produk_17-Feb-2026_halaman-1.csv')
for sku in sorted(p_map.keys()):
    print(f"{sku}: {p_map[sku]}")

print("\n--- ASET TETAP CSV FA/ ITEMS ---")
a_map = get_mapping(r'd:\Program Receh\kledo\data-baru\aset-tetap_17-Feb-2026_halaman-1.csv')
for sku in sorted(a_map.keys()):
    print(f"{sku}: {a_map[sku]}")
