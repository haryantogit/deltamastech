import csv

def get_product_items(path):
    data = [] # List of (SKU, Name)
    with open(path, mode='r', encoding='utf-8') as f:
        reader = csv.reader(f)
        next(reader)
        for row in reader:
            if not row: continue
            data.append((row[1].strip(), row[0].strip()))
    return data

p_items = get_product_items(r'd:\Program Receh\kledo\data-baru\produk_17-Feb-2026_halaman-1.csv')

def find_p_sku(name):
    name_l = name.lower()
    # Try exact or close match
    for sku, p_name in p_items:
        p_l = p_name.lower()
        if name_l == p_l: return sku
        # Try some normalization
        n1 = name_l.replace('moulding', 'mould').replace('  ', ' ')
        p1 = p_l.replace('moulding', 'mould').replace('  ', ' ')
        if n1 == p1: return sku
    return None

print("Asset Name | Asset SKU | Target Produk SKU")
aset_path = r'd:\Program Receh\kledo\data-baru\aset-tetap_17-Feb-2026_halaman-1.csv'
with open(aset_path, mode='r', encoding='utf-8') as f:
    reader = csv.reader(f)
    next(reader)
    for row in reader:
        if not row: continue
        a_name = row[0].strip()
        a_sku = row[1].strip()
        target_sku = find_p_sku(a_name)
        print(f"'{a_name}' | {a_sku} | {target_sku}")
