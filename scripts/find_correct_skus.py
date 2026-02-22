import csv

def get_product_map(path):
    # Map Name.lower() -> SKU
    # Name is column 0, SKU is column 1
    data = {}
    with open(path, mode='r', encoding='utf-8') as f:
        reader = csv.reader(f)
        next(reader)
        for row in reader:
            if not row: continue
            name = row[0].strip().lower()
            sku = row[1].strip()
            # If multiple SKUs for same name, we might have issues, but let's see
            data[name] = sku
    return data

product_name_to_sku = get_product_map(r'd:\Program Receh\kledo\data-baru\produk_17-Feb-2026_halaman-1.csv')

print("Mapping Aset -> Produk SKUs based on Name:")
aset_path = r'd:\Program Receh\kledo\data-baru\aset-tetap_17-Feb-2026_halaman-1.csv'
with open(aset_path, mode='r', encoding='utf-8') as f:
    reader = csv.reader(f)
    next(reader)
    for row in reader:
        if not row: continue
        a_name = row[0].strip()
        a_sku = row[1].strip()
        
        # Fuzzy/Normalized match
        clean_name = a_name.lower().replace('moulding', 'mould').replace('inc', 'inchi').replace('  ', ' ')
        
        match_sku = None
        # Try exact match first
        if a_name.lower() in product_name_to_sku:
            match_sku = product_name_to_sku[a_name.lower()]
        else:
            # Try some common variations
            for p_name, p_sku in product_name_to_sku.items():
                p_clean = p_name.replace('moulding', 'mould').replace('inc', 'inchi').replace('  ', ' ')
                if clean_name in p_clean or p_clean in clean_name:
                    match_sku = p_sku
                    break
        
        if match_sku:
            status = "MATCH" if a_sku == match_sku else f"FIX NEEDED ({a_sku} -> {match_sku})"
            print(f"'{a_name}': {status}")
        else:
            print(f"'{a_name}': NO MATCH IN PRODUK.CSV")
