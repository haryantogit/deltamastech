<x-filament-panels::page>
    <style>
        .fav-header-card {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 28px 32px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }

        .fav-header-icon {
            width: 48px;
            height: 48px;
            background: rgba(251, 191, 36, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .fav-header-icon svg {
            width: 24px;
            height: 24px;
            color: #fbbf24;
        }

        .fav-header-text h2 {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 4px 0;
        }

        .fav-header-text p {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
            line-height: 1.5;
        }

        .fav-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 1200px) {
            .fav-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .fav-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .fav-grid {
                grid-template-columns: 1fr;
            }
        }

        .fav-add-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 220px;
            border: 2px dashed #334155;
            border-radius: 16px;
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
            padding: 24px;
        }

        .fav-add-btn:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .fav-add-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            transition: all 0.2s;
        }

        .fav-add-btn:hover .fav-add-icon {
            background: rgba(59, 130, 246, 0.15);
        }

        .fav-add-icon svg {
            width: 22px;
            height: 22px;
            color: #64748b;
            transition: color 0.2s;
        }

        .fav-add-btn:hover .fav-add-icon svg {
            color: #3b82f6;
        }

        .fav-add-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            transition: color 0.2s;
        }

        .fav-add-btn:hover .fav-add-label {
            color: #3b82f6;
        }

        .fav-card {
            position: relative;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .fav-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.12);
            transform: translateY(-2px);
        }

        .fav-card-img {
            width: 100%;
            aspect-ratio: 1;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .fav-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .fav-card-img svg {
            width: 40px;
            height: 40px;
            color: #e2e8f0;
        }

        .fav-card-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .fav-card:hover .fav-card-overlay {
            opacity: 1;
        }

        .fav-card-delete {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ef4444;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
        }

        .fav-card-delete:hover {
            background: #dc2626;
            transform: scale(1.1);
        }

        .fav-card-delete svg {
            width: 18px;
            height: 18px;
            color: #fff;
        }

        .fav-card-body {
            padding: 14px 16px;
        }

        .fav-card-sku {
            font-size: 10px;
            font-weight: 700;
            color: #3b82f6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
        }

        .fav-card-name {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 36px;
        }

        .fav-card-outlet {
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            color: #3b82f6;
        }

        .fav-card-outlet.global {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .fav-card-outlet svg {
            width: 10px;
            height: 10px;
        }

        .fav-tip {
            margin-top: 24px;
            background: rgba(59, 130, 246, 0.06);
            border: 1px solid rgba(59, 130, 246, 0.12);
            border-radius: 12px;
            padding: 14px 20px;
            text-align: center;
        }

        .fav-tip p {
            font-size: 12px;
            color: #64748b;
            margin: 0;
        }

        .fav-tip a {
            color: #3b82f6;
            font-weight: 700;
            text-decoration: underline;
        }

        /* Dark mode */
        .dark .fav-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .fav-card:hover {
            border-color: #3b82f6;
        }

        .dark .fav-card-img {
            background: #0f172a;
        }

        .dark .fav-card-name {
            color: #f1f5f9;
        }

        .dark .fav-add-btn {
            border-color: #334155;
        }

        .dark .fav-add-btn:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .dark .fav-tip {
            background: rgba(59, 130, 246, 0.05);
            border-color: rgba(59, 130, 246, 0.1);
        }
    </style>

    {{-- Header Banner --}}
    <div class="fav-header-card">
        <div class="fav-header-icon">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
        </div>
        <div class="fav-header-text">
            <h2>Produk Favorit</h2>
            <p>Pilih produk yang akan ditampilkan di halaman depan POS Kasir untuk memudahkan pencarian produk.</p>
        </div>
    </div>

    {{-- Product Grid --}}
    <div class="fav-grid">
        {{-- Add Button --}}
        <button type="button" wire:click="mountAction('addFavorite')" class="fav-add-btn">
            <div class="fav-add-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <span class="fav-add-label">Tambah Produk Favorit</span>
        </button>

        {{-- Favorite Cards --}}
        @foreach($favorites as $favorite)
            <div class="fav-card">
                <div class="fav-card-img">
                    @if($favorite->product->image ?? false)
                        <img src="{{ Storage::disk('public')->url($favorite->product->image) }}"
                            alt="{{ $favorite->product->name }}">
                    @else
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    @endif
                    <div class="fav-card-overlay">
                        <button type="button" wire:click="deleteFavorite({{ $favorite->id }})"
                            wire:confirm="Hapus produk ini dari favorit?" class="fav-card-delete">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="fav-card-body">
                    <p class="fav-card-sku">{{ $favorite->product->sku ?? '-' }}</p>
                    <p class="fav-card-name">{{ $favorite->product->name }}</p>
                    <div class="fav-card-outlet {{ $favorite->outlet ? '' : 'global' }}">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        {{ $favorite->outlet->name ?? 'Semua Outlet' }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Info Tip --}}
    <div class="fav-tip">
        <p>Halaman ini untuk mengatur produk yang dijadikan favorit di POS. Jika Anda ingin menambah produk baru,
            silahkan <a href="{{ \App\Filament\Resources\ProductResource::getUrl('create') }}">klik disini</a>.</p>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>