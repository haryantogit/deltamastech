@php
    $viewData = $this->getViewData();
    $contacts = $viewData['results'];
    $paginator = $viewData['paginator'];
    $totalCount = $viewData['totalCount'];
    $globalPendapatan = $viewData['globalPendapatan'];
    $expandedContacts = $this->expandedContacts;

    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };
@endphp

<x-filament-panels::page>
    <style>
        .revenue-customer-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .revenue-customer-container {
            background: #111827;
            border-color: #374151;
        }

        /* Filter & Search row */
        .filter-search-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            gap: 1rem;
        }

        .dark .filter-search-row {
            border-color: #374151;
        }

        .date-display {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .custom-search-container {
            position: relative;
            width: 280px;
        }

        .search-icon-abs {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: #94a3b8;
        }

        .custom-search-input {
            width: 100%;
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8125rem;
            background: white;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s;
        }

        .dark .custom-search-input {
            background: #1f2937;
            border-color: #374151;
            color: #f1f5f9;
        }

        .custom-search-input:focus {
            border-color: #3b82f6;
        }

        /* Main Table */
        .revenue-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .revenue-table th {
            padding: 0.875rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: left;
        }

        .dark .revenue-table th {
            background: #1f2937;
            border-bottom-color: #374151;
            color: #94a3b8;
        }

        .revenue-table td {
            padding: 0.75rem 1rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .revenue-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .row-clickable {
            cursor: pointer;
            transition: background 0.15s;
        }

        .row-clickable:hover {
            background: rgba(59, 130, 246, 0.02);
        }

        .dark .row-clickable:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .expand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            font-size: 0.875rem;
            font-weight: 700;
            color: #94a3b8;
            border-radius: 50%;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .dark .expand-icon {
            border-color: #4b5563;
        }

        .row-clickable:hover .expand-icon {
            color: #3b82f6;
            border-color: #3b82f6;
        }

        .contact-name {
            font-weight: 600;
            color: #334155;
        }

        .dark .contact-name {
            color: #e2e8f0;
        }

        .pendapatan-amount {
            color: #3b82f6;
            font-weight: 600;
        }

        /* Expanded Detail */
        .details-wrapper {
            background: rgba(59, 130, 246, 0.01);
            padding: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .dark .details-wrapper {
            background: rgba(255, 255, 255, 0.01);
            border-color: #374151;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
            overflow: hidden;
        }

        .dark .details-table {
            background: #111827;
            border-color: #374151;
        }

        .details-table th {
            background: rgba(59, 130, 246, 0.04);
            color: #475569;
            padding: 0.625rem 0.75rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
        }

        .dark .details-table th {
            background: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
        }

        .details-table td {
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.75rem;
        }

        .dark .details-table td {
            border-bottom-color: #1e293b;
        }

        .invoice-link {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
        }

        .invoice-link:hover {
            text-decoration: underline;
        }

        /* Pagination */
        .pagination-container {
            padding: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .pagination-status {
            font-size: 0.8125rem;
            color: #64748b;
            font-weight: 500;
        }

        .per-page-capsule {
            display: flex;
            align-items: center;
            background: rgba(128, 128, 128, 0.05);
            border: 1px solid rgba(128, 128, 128, 0.1);
            border-radius: 10px;
            padding: 0 0.75rem;
            height: 2.25rem;
            gap: 0;
        }

        .per-page-label {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
            border-right: 1px solid rgba(128, 128, 128, 0.1);
            padding-right: 0.75rem;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .per-page-capsule select {
            background: transparent;
            border: none;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1e293b;
            outline: none;
            cursor: pointer;
            padding: 0 0.5rem 0 0.75rem;
            margin: 0;
            appearance: none;
            -webkit-appearance: none;
        }

        .dark .per-page-capsule select {
            color: #f1f5f9;
        }

        .numeric-capsule nav {
            display: flex;
            align-items: center;
        }

        .numeric-capsule nav>div:first-child,
        .numeric-capsule nav p,
        .numeric-capsule [class*="hidden sm:flex-1"] {
            display: none !important;
        }

        .numeric-capsule nav div:last-child {
            display: flex !important;
            background: rgba(128, 128, 128, 0.05) !important;
            border: 1px solid rgba(128, 128, 128, 0.1) !important;
            border-radius: 10px !important;
            overflow: hidden !important;
        }

        .numeric-capsule a,
        .numeric-capsule span {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-width: 2.5rem !important;
            height: 2.25rem !important;
            padding: 0 0.75rem !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            border: none !important;
            border-right: 1px solid rgba(128, 128, 128, 0.1) !important;
            background: transparent !important;
            transition: all 0.2s !important;
            text-decoration: none !important;
        }

        .dark .numeric-capsule a,
        .dark .numeric-capsule span {
            color: #f1f5f9 !important;
        }

        .numeric-capsule div:last-child> :last-child {
            border-right: none !important;
        }

        .numeric-capsule a:hover {
            background: rgba(59, 130, 246, 0.05) !important;
            color: #3b82f6 !important;
        }

        .numeric-capsule .active span,
        .numeric-capsule [aria-current="page"] span {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
            font-weight: 700 !important;
        }

        .numeric-capsule svg {
            width: 1rem !important;
            height: 1rem !important;
        }

        @media print {

            .fi-header-actions,
            .filter-search-row,
            .pagination-container {
                display: none !important;
            }
        }
    </style>

    <div class="revenue-customer-container">
        {{-- Search Row --}}
        <div class="filter-search-row">
            <div class="date-display">
                <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
                {{ \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') }} —
                {{ \Carbon\Carbon::parse($this->endDate)->format('d/m/Y') }}
            </div>
            <div class="custom-search-container">
                <svg class="search-icon-abs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search"
                    placeholder="Cari nama pelanggan, perusahaan..." class="custom-search-input">
            </div>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table class="revenue-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Pelanggan</th>
                        <th>Perusahaan</th>
                        <th>Telepon</th>
                        <th style="text-align: right;">Total Transaksi</th>
                        <th style="text-align: right;">Total Qty</th>
                        <th style="text-align: right;">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                        @php $isExpanded = in_array($contact['id'], $expandedContacts); @endphp
                        <tr class="row-clickable" wire:click="toggleContact({{ $contact['id'] }})">
                            <td>
                                <span class="expand-icon">{{ $isExpanded ? '−' : '+' }}</span>
                            </td>
                            <td><span class="contact-name">{{ $contact['name'] }}</span></td>
                            <td style="color: #64748b;">{{ $contact['company'] }}</td>
                            <td style="color: #64748b;">{{ $contact['phone'] }}</td>
                            <td style="text-align: right; font-weight: 500;">{{ $fmt($contact['total_transaksi']) }}</td>
                            <td style="text-align: right;">{{ $fmt($contact['total_qty']) }}</td>
                            <td style="text-align: right; padding-right: 1.5rem;" class="pendapatan-amount">
                                {{ $fmt($contact['pendapatan']) }}
                            </td>
                        </tr>

                        @if($isExpanded)
                            <tr>
                                <td colspan="7" style="padding: 0;">
                                    <div class="details-wrapper">
                                        <table class="details-table">
                                            <thead>
                                                <tr>
                                                    <th>Nomor</th>
                                                    <th>Tanggal</th>
                                                    <th>Produk</th>
                                                    <th>Kode/SKU</th>
                                                    <th>Kategori</th>
                                                    <th>Deskripsi</th>
                                                    <th style="text-align: right;">Kuantitas</th>
                                                    <th style="text-align: right;">Harga</th>
                                                    <th style="text-align: right; padding-right: 1.5rem;">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($contact['items'] as $item)
                                                    <tr>
                                                        <td><a href="#" class="invoice-link">{{ $item['invoice_number'] }}</a></td>
                                                        <td style="color: #64748b;">{{ $item['date'] }}</td>
                                                        <td style="font-weight: 500;">{{ $item['product_name'] }}</td>
                                                        <td style="color: #64748b;">{{ $item['product_sku'] }}</td>
                                                        <td style="color: #64748b;">{{ $item['category_name'] }}</td>
                                                        <td style="color: #64748b; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                            title="{{ $item['description'] }}">{{ $item['description'] }}</td>
                                                        <td style="text-align: right;">{{ $fmt($item['qty']) }}</td>
                                                        <td style="text-align: right;">{{ $fmt($item['price']) }}</td>
                                                        <td style="text-align: right; font-weight: 600; padding-right: 1.5rem;">
                                                            {{ $fmt($item['total']) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="9" style="text-align: center; padding: 1rem; color: #94a3b8;">
                                                            Tidak ada detail barang.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data pelanggan untuk rentang ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    {{-- Global Total --}}
                    <tr style="border-top: 2px solid #e2e8f0;" class="dark:border-gray-700">
                        <td colspan="6" style="font-weight: 800; padding: 1.25rem 1rem;">Total</td>
                        <td style="text-align: right; font-weight: 800; padding: 1.25rem 1.5rem 1.25rem 1rem; color: #334155;"
                            class="dark:text-gray-100">{{ $fmt($globalPendapatan) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Pagination Footer --}}
    <div class="pagination-container">
        <div class="pagination-status">
            Total {{ number_format($totalCount, 0, ',', '.') }} data
        </div>

        <div class="per-page-capsule">
            <span class="per-page-label">per halaman</span>
            <select wire:model.live="perPage">
                <option value="15">15</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
            </select>
            <svg style="width: 1rem; height: 1rem; color: #64748b; margin-left: -0.25rem;" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>

        <div class="numeric-capsule">
            {{ $paginator->links() }}
        </div>
    </div>

</x-filament-panels::page>