# PROJECT SPECIFICATIONS: Kledo ERP Clone
**Goal:** Build a modular ERP system for SME using Laravel 11 & FilamentPHP v3.
**Context:** Based on `Db.txt` CSV schemas provided by the user.

---

## 1. TECH STACK (NON-NEGOTIABLE)
- **Framework:** Laravel 11.x
- **Admin Panel:** FilamentPHP v3 (Must use for all Resources/Pages).
- **Database:** MySQL 8.0+
- **Excel Import:** `maatwebsite/excel` (Critical for processing the CSV schemas).

---

## 2. CORE DATABASE SCHEMA & LOGIC

### [cite_start]A. Module: Finance (Akun) 
**Table:** `accounts`
- **Columns:** `code` (string, unique), `name`, `category` (enum: Asset, Liability, Equity, Income, Expense), `parent_id` (self-reference for Sub-Akun), `current_balance` (decimal).
- **Logic:**
  - `current_balance` must be updated via Event/Listener whenever a transaction (Journal Entry) is created.

### [cite_start]B. Module: Contacts (Kontak) 
**Table:** `contacts`
- **Columns:** `name`, `type` (enum: Customer, Vendor, Employee), `company`, `email`, `phone`, `npwp`, `credit_limit` (Maksimal Hutang), `receivable_limit` (Maksimal Piutang).
- **Validation:** Prevent Sales Order creation if `receivable_limit` is exceeded.

### [cite_start]C. Module: Products (Inventory) [cite: 1, 2]
**Table:** `products`
- **Columns:** `name`, `sku`, `type`, `buy_price`, `sell_price`, `stock`, `min_stock`.
- **Type Enum:**
  1.  `standard`: Regular item.
  2.  `service`: No stock tracking.
  3.  `manufacturing`: Has Bill of Materials (BoM).
  4.  `bundle`: Composed of other products.

**Table:** `product_materials` (For Manufacturing)
- **Pivot Table:** links `product_id` (parent) to `material_id` (child product).
- **Columns:** `quantity_needed`.
- **Logic:** When a Manufacturing Product is sold/created, deduct stock from `material_id`.

**Table:** `product_bundles` (For Paket)
- **Pivot Table:** links `product_id` (bundle) to `item_id` (child product).
- **Logic:** Bundle stock is virtual, calculated based on the lowest available stock of its components.

### [cite_start]D. Module: Sales & Purchasing (Transactions) [cite: 3, 4, 5, 6, 7, 8]
**Architecture:** Use "Header-Detail" structure for all transaction types.

**1. Headers Table (Polymorphic or Separate):**
- Create `sales_invoices`, `sales_orders`, `purchase_invoices`, `purchase_orders`.
- **Common Columns:** `contact_id`, `transaction_date`, `due_date`, `status` (Draft, Unpaid, Paid, Cancelled), `warehouse_id`.

**2. Items Table:**
- Create `transaction_items`.
- **Columns:** `transaction_type` (Morph), `transaction_id` (Morph), `product_id`, `description`, `qty`, `unit`, `price`, `discount_percent`, `tax_amount`, `subtotal`.

**3. Specific Logic based on `Db.txt`:**
- [cite_start]**Sales Invoice (`TAGIHAN PENJUALAN`)[cite: 6]:** Must trigger an accounting journal (Debit: Account Receivable, Credit: Sales Revenue).
- [cite_start]**Purchase Invoice (`TAGIHAN PEMBELIAN`)[cite: 3]:** Must trigger an accounting journal (Debit: Expense/Inventory, Credit: Account Payable).
- **Manufacturing Execution:** Needs a custom action to "Produce" items, which triggers stock deduction of raw materials defined in ``.

---

## 3. FILAMENT IMPLEMENTATION GUIDE
1.  **Forms:**
    - Use `Repeater::make('items')` for transaction details (products in invoice).
    - Use `live()` on Quantity and Price fields to auto-calculate Subtotal.
    - Use `Select::make('contact_id')` with `searchable()`.
2.  **Tables:**
    - Use `Summarizers` for Total Amount columns.
    - Use `Filter` for Date Range and Status.
3.  **Import Feature:**
    - Create a custom Filament Action `ImportData` that maps the CSV headers from `Db.txt` to the database columns defined above.

---

## 4. NEXT STEP INSTRUCTIONS FOR AGENT
1.  **Step 1:** Create Migrations for `accounts`, `contacts`, and `products` (including pivot tables).
2.  **Step 2:** Create Filament Resources for these masters.
3.  **Step 3:** Create Migrations for `sales_invoices` and `sales_invoice_items`.