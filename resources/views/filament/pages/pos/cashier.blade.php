<div>
    <style>
        :root {
            --pos-bg: #f1f5f9;
            --pos-surface: #ffffff;
            --pos-surface-hover: #f8fafc;
            --pos-border: #e2e8f0;
            --pos-text: #1e293b;
            --pos-text-muted: #64748b;
            --pos-input-bg: #f8fafc;
            --pos-card-bg: #ffffff;
            --pos-accent: #3b82f6;
            --pos-accent-hover: #2563eb;
            --pos-shadow: rgba(0,0,0,0.05);
        }

        /* Dark Mode Support (Filament standard) */
        :root.dark,
        .dark,
        [data-theme='dark'] {
            --pos-bg: #0f172a;
            --pos-surface: #1e293b;
            --pos-surface-hover: #334155;
            --pos-border: #334155;
            --pos-text: #f1f5f9;
            --pos-text-muted: #94a3b8;
            --pos-input-bg: #0f172a;
            --pos-card-bg: #1e293b;
            --pos-accent: #3b82f6;
            --pos-accent-hover: #2563eb;
            --pos-shadow: rgba(0,0,0,0.3);
        }

        /* Force variables on specific elements */
        .pos-cashier {
            display: flex;
            height: calc(100vh - 64px);
            overflow: hidden;
            background: var(--pos-bg);
            color: var(--pos-text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        /* === LEFT: Product Area === */
        .pos-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .pos-search-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            background: var(--pos-surface);
            border-bottom: 1px solid var(--pos-border);
        }

        .pos-back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--pos-input-bg);
            border: 1px solid var(--pos-border);
            border-radius: 10px;
            color: var(--pos-text-muted);
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
        }

        .pos-back-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-color: #475569;
        }

        .pos-back-btn svg {
            width: 18px;
            height: 18px;
        }

        .pos-search-input {
            flex: 1;
            padding: 12px 16px;
            background: var(--pos-input-bg);
            border: 1px solid var(--pos-border);
            border-radius: 12px;
            color: var(--pos-text);
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        .pos-search-input:focus { border-color: var(--pos-accent); }
        .pos-search-input::placeholder { color: var(--pos-text-muted); }

        .pos-outlet-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 12px;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .pos-outlet-badge svg { width: 16px; height: 16px; }

        /* Category Select */
        .pos-category-select {
            padding: 8px 32px 8px 12px;
            background: var(--pos-input-bg);
            border: 1px solid var(--pos-border);
            border-radius: 10px;
            color: var(--pos-text);
            font-size: 13px;
            font-weight: 700;
            outline: none;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            min-width: 140px;
            transition: all 0.2s;
        }

        .pos-category-select:focus {
            border-color: var(--pos-accent);
        }

        /* Hide old categories bar */
        .pos-categories {
            display: none;
        }

        /* Product Grid */
        .pos-products-area {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px;
        }

        .pos-section-label {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--pos-text-muted);
            margin: 0 0 12px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pos-section-label.favorites {
            color: #fbbf24;
            margin-bottom: 16px;
        }

        .pos-section-label svg { width: 14px; height: 14px; }

        .pos-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .pos-product-card {
            background: var(--pos-card-bg);
            border: 1px solid var(--pos-border);
            border-radius: 14px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.15s;
            user-select: none;
            box-shadow: 0 2px 8px var(--pos-shadow);
        }

        .pos-product-card:hover {
            border-color: var(--pos-accent);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--pos-shadow);
        }

        .pos-product-card:active {
            transform: scale(0.97);
        }

        .pos-product-img {
            width: 100%;
            aspect-ratio: 1;
            background: var(--pos-input-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .pos-product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .pos-product-img svg {
            width: 32px;
            height: 32px;
            color: var(--pos-text-muted);
        }

        .pos-product-info {
            padding: 10px 12px;
        }

        .pos-product-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--pos-text);
            margin: 0 0 4px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pos-product-price {
            font-size: 13px;
            font-weight: 800;
            color: var(--pos-accent);
            margin: 0;
        }

        .pos-product-sku {
            font-size: 10px;
            color: var(--pos-text-muted);
            margin: 2px 0 0 0;
        }

        /* === RIGHT: Cart Area === */
        .pos-right {
            width: 380px;
            min-width: 380px;
            display: flex;
            flex-direction: column;
            background: var(--pos-surface);
            border-left: 1px solid var(--pos-border);
        }

        .pos-cart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--pos-border);
        }

        .pos-cart-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--pos-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pos-cart-title svg { width: 20px; height: 20px; color: #fbbf24; }

        .pos-cart-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            background: #3b82f6;
            border-radius: 50%;
            color: #fff;
            font-size: 11px;
            font-weight: 800;
        }

        .pos-cart-clear {
            padding: 6px 12px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            color: #ef4444;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
        }
        .pos-cart-clear:hover { background: rgba(239, 68, 68, 0.2); }

        /* Cart Items */
        .pos-cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 12px 16px;
        }

        .pos-cart-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--pos-text-muted);
            text-align: center;
        }

        .pos-cart-empty svg { width: 48px; height: 48px; margin-bottom: 12px; color: #334155; }
        .pos-cart-empty p { font-size: 13px; margin: 0; }

        .pos-cart-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: var(--pos-input-bg);
            border-radius: 12px;
            margin-bottom: 8px;
            transition: background 0.1s;
        }

        .pos-cart-item-info { flex: 1; min-width: 0; }

        .pos-cart-item-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--pos-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pos-cart-item-price {
            font-size: 11px;
            color: var(--pos-text-muted);
        }

        .pos-cart-item-subtotal {
            font-size: 13px;
            font-weight: 800;
            color: var(--pos-accent);
            white-space: nowrap;
        }

        .pos-cart-qty {
            display: flex;
            align-items: center;
            gap: 0;
            background: var(--pos-surface);
            border-radius: 8px;
            overflow: hidden;
        }

        .pos-cart-qty button {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            transition: all 0.1s;
        }
        .pos-cart-qty button:hover { background: var(--pos-surface-hover); color: var(--pos-text); }

        .pos-cart-qty span {
            width: 28px;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            color: var(--pos-text);
        }

        .pos-cart-remove {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: none;
            color: #475569;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.1s;
        }
        .pos-cart-remove:hover { color: #ef4444; background: rgba(239,68,68,0.1); }
        .pos-cart-remove svg { width: 14px; height: 14px; }

        /* Summary */
        .pos-cart-summary {
            padding: 16px 20px;
            border-top: 1px solid var(--pos-border);
            background: var(--pos-input-bg);
        }

        .pos-summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .pos-summary-label { font-size: 13px; color: var(--pos-text-muted); }
        .pos-summary-value { font-size: 13px; font-weight: 700; color: var(--pos-text); }

        .pos-summary-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0 0;
            border-top: 1px solid #334155;
            margin-top: 4px;
        }

        .pos-summary-total .pos-summary-label {
            font-size: 16px;
            font-weight: 800;
            color: var(--pos-text);
        }

        .pos-summary-total .pos-summary-value {
            font-size: 22px;
            font-weight: 900;
            color: #22c55e;
        }

        /* Pay Button */
        .pos-pay-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            color: #fff;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .pos-pay-btn:hover { filter: brightness(1.1); }
        .pos-pay-btn:active { transform: scale(0.99); }
        .pos-pay-btn:disabled {
            background: var(--pos-border);
            color: var(--pos-text-muted);
            cursor: not-allowed;
            filter: none;
        }
        .pos-pay-btn svg { width: 20px; height: 20px; }

        /* === Payment Modal === */
        .pos-modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        }

        .pos-modal {
            background: var(--pos-surface);
            border: 1px solid var(--pos-border);
            border-radius: 20px;
            padding: 32px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 24px 48px var(--pos-shadow);
        }

        .pos-modal-title {
            font-size: 20px;
            font-weight: 900;
            color: var(--pos-text);
            margin: 0 0 24px 0;
            text-align: center;
        }

        .pos-modal-total {
            text-align: center;
            margin-bottom: 24px;
            padding: 16px;
            background: var(--pos-input-bg);
            border-radius: 14px;
        }

        .pos-modal-total-label {
            font-size: 12px;
            color: var(--pos-text-muted);
            margin: 0 0 4px 0;
            text-transform: uppercase;
            font-weight: 700;
        }

        .pos-modal-total-value {
            font-size: 32px;
            font-weight: 900;
            color: #22c55e;
            margin: 0;
        }

        .pos-modal-field {
            margin-bottom: 16px;
        }

        .pos-modal-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--pos-text-muted);
            margin-bottom: 6px;
        }

        .pos-modal-input {
            width: 100%;
            padding: 12px 14px;
            background: var(--pos-input-bg);
            border: 1px solid var(--pos-border);
            border-radius: 10px;
            color: var(--pos-text);
            font-size: 14px;
            outline: none;
            box-sizing: border-box;
        }
        .pos-modal-input:focus { border-color: #3b82f6; }

        .pos-payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }

        .pos-payment-method {
            padding: 12px;
            text-align: center;
            border: 2px solid var(--pos-border);
            border-radius: 12px;
            background: var(--pos-input-bg);
            color: var(--pos-text-muted);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
        }
        .pos-payment-method:hover { border-color: #475569; }
        .pos-payment-method.active {
            border-color: #3b82f6;
            background: rgba(59,130,246,0.1);
            color: #3b82f6;
        }

        .pos-modal-change {
            text-align: center;
            padding: 12px;
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.2);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .pos-modal-change-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            margin: 0 0 2px 0;
        }

        .pos-modal-change-value {
            font-size: 20px;
            font-weight: 900;
            color: #22c55e;
            margin: 0;
        }

        .pos-modal-actions {
            display: flex;
            gap: 10px;
        }

        .pos-modal-cancel {
            flex: 1;
            padding: 14px;
            background: #334155;
            border: none;
            border-radius: 12px;
            color: #94a3b8;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
        }
        .pos-modal-cancel:hover { background: #475569; }

        .pos-modal-confirm {
            flex: 2;
            padding: 14px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.15s;
        }
        .pos-modal-confirm:hover { filter: brightness(1.1); }

        /* Scrollbar */
        .pos-products-area::-webkit-scrollbar,
        .pos-cart-items::-webkit-scrollbar { width: 6px; }
        .pos-products-area::-webkit-scrollbar-track,
        .pos-cart-items::-webkit-scrollbar-track { background: transparent; }
        .pos-products-area::-webkit-scrollbar-thumb,
        .pos-cart-items::-webkit-scrollbar-thumb { background: #334155; border-radius: 3px; }
    </style>

    <div class="pos-cashier">
        {{-- LEFT: Products --}}
        <div class="pos-left">
            {{-- Search Bar --}}
            <div class="pos-search-bar">
                <a href="{{ url('/admin/web-pos-page') }}" class="pos-back-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    Kembali
                </a>

                <select class="pos-category-select" wire:model.live="activeCategoryId">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                <input type="text"
                    class="pos-search-input"
                    placeholder="Cari produk berdasarkan nama atau SKU..."
                    wire:model.live.debounce.300ms="search" />

                @if($outlet)
                    <div class="pos-outlet-badge">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        {{ $outlet->name }}
                    </div>
                @endif
            </div>

            {{-- Category Filter (Now moved to search bar as select) --}}
            {{--
            <div class="pos-categories">
                ...
            </div>
            --}}

            {{-- Products Grid --}}
            <div class="pos-products-area">
                {{-- Favorites --}}
                @if($favorites->count() > 0 && empty($search))
                    <p class="pos-section-label favorites">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" /></svg>
                        Produk Favorit
                    </p>
                    <div class="pos-product-grid">
                        @foreach($favorites as $fav)
                            @if($fav->product)
                                <div class="pos-product-card" wire:click="addToCart({{ $fav->product->id }})">
                                    <div class="pos-product-img">
                                        @if(is_array($fav->product->image) && count($fav->product->image) > 0)
                                            <img src="{{ Storage::disk('public')->url($fav->product->image[0]) }}" alt="{{ $fav->product->name }}">
                                        @else
                                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                        @endif
                                    </div>
                                    <div class="pos-product-info">
                                        <p class="pos-product-name">{{ $fav->product->name }}</p>
                                        <p class="pos-product-price">Rp {{ number_format($fav->product->sell_price, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- All Products --}}
                <p class="pos-section-label">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    @if($search) Hasil Pencarian @else Semua Produk @endif
                </p>
                <div class="pos-product-grid">
                    @forelse($products as $product)
                        <div class="pos-product-card" wire:click="addToCart({{ $product->id }})">
                            <div class="pos-product-img">
                                @if(is_array($product->image) && count($product->image) > 0)
                                    <img src="{{ Storage::disk('public')->url($product->image[0]) }}" alt="{{ $product->name }}">
                                @else
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                @endif
                            </div>
                            <div class="pos-product-info">
                                <p class="pos-product-name">{{ $product->name }}</p>
                                <p class="pos-product-price">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</p>
                                @if($product->sku)
                                    <p class="pos-product-sku">{{ $product->sku }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="grid-column:1/-1;text-align:center;padding:40px 0;color:#475569">
                            <p style="font-size:14px">Tidak ada produk ditemukan</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT: Cart --}}
        <div class="pos-right">
            {{-- Cart Header --}}
            <div class="pos-cart-header">
                <div class="pos-cart-title">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>
                    Keranjang
                    @if(count($cart) > 0)
                        <span class="pos-cart-badge">{{ count($cart) }}</span>
                    @endif
                </div>
                @if(count($cart) > 0)
                    <button class="pos-cart-clear" wire:click="clearCart">Hapus Semua</button>
                @endif
            </div>

            {{-- Cart Items --}}
            <div class="pos-cart-items">
                @if(count($cart) === 0)
                    <div class="pos-cart-empty">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" /></svg>
                        <p>Keranjang masih kosong</p>
                        <p style="font-size:11px;color:#334155;margin-top:4px">Klik produk untuk menambahkan</p>
                    </div>
                @else
                    @foreach($cart as $index => $item)
                        <div class="pos-cart-item">
                            <div class="pos-cart-item-info">
                                <div class="pos-cart-item-name">{{ $item['name'] }}</div>
                                <div class="pos-cart-item-price">Rp {{ number_format($item['price'], 0, ',', '.') }} × {{ $item['qty'] }}</div>
                            </div>
                            <div class="pos-cart-qty">
                                <button wire:click="decrementQty({{ $index }})">−</button>
                                <span>{{ $item['qty'] }}</span>
                                <button wire:click="incrementQty({{ $index }})">+</button>
                            </div>
                            <div class="pos-cart-item-subtotal">Rp {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</div>
                            <button class="pos-cart-remove" wire:click="removeFromCart({{ $index }})">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Summary --}}
            <div class="pos-cart-summary">
                <div class="pos-summary-row">
                    <span class="pos-summary-label">Subtotal</span>
                    <span class="pos-summary-value">Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="pos-summary-row">
                    <span class="pos-summary-label">Pajak</span>
                    <span class="pos-summary-value">Rp {{ number_format($this->tax, 0, ',', '.') }}</span>
                </div>
                <div class="pos-summary-total">
                    <span class="pos-summary-label">Total</span>
                    <span class="pos-summary-value">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Pay Button --}}
            <button class="pos-pay-btn" wire:click="openPayment" @if(count($cart) === 0) disabled @endif>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                Bayar — Rp {{ number_format($this->total, 0, ',', '.') }}
            </button>
        </div>
    </div>

    {{-- Payment Modal --}}
    @if($showPayment)
        <div class="pos-modal-overlay" wire:click.self="closePayment">
            <div class="pos-modal">
                <h2 class="pos-modal-title">💰 Pembayaran</h2>

                <div class="pos-modal-total">
                    <p class="pos-modal-total-label">Total Tagihan</p>
                    <p class="pos-modal-total-value">Rp {{ number_format($this->total, 0, ',', '.') }}</p>
                </div>

                {{-- Payment Method --}}
                <div class="pos-modal-field">
                    <label class="pos-modal-label">Metode Pembayaran</label>
                    <div class="pos-payment-methods">
                        <div class="pos-payment-method {{ $paymentMethod === 'cash' ? 'active' : '' }}" wire:click="$set('paymentMethod', 'cash')">
                            💵 Tunai
                        </div>
                        <div class="pos-payment-method {{ $paymentMethod === 'qris' ? 'active' : '' }}" wire:click="$set('paymentMethod', 'qris')">
                            📱 QRIS
                        </div>
                        <div class="pos-payment-method {{ $paymentMethod === 'transfer' ? 'active' : '' }}" wire:click="$set('paymentMethod', 'transfer')">
                            🏦 Transfer
                        </div>
                    </div>
                </div>

                {{-- Customer Name --}}
                <div class="pos-modal-field">
                    <label class="pos-modal-label">Nama Pelanggan (opsional)</label>
                    <input type="text" class="pos-modal-input" wire:model="customerName" placeholder="Nama pelanggan">
                </div>

                {{-- Cash Received --}}
                @if($paymentMethod === 'cash')
                    <div class="pos-modal-field">
                        <label class="pos-modal-label">Uang Diterima</label>
                        <input type="number" class="pos-modal-input" wire:model.live="cashReceived" placeholder="0" style="font-size:18px;font-weight:800">
                    </div>

                    <div class="pos-modal-change">
                        <p class="pos-modal-change-label">Kembalian</p>
                        <p class="pos-modal-change-value">Rp {{ number_format($this->change, 0, ',', '.') }}</p>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="pos-modal-actions">
                    <button class="pos-modal-cancel" wire:click="closePayment">Batal</button>
                    <button class="pos-modal-confirm" wire:click="processPayment">
                        ✅ Proses Pembayaran
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
