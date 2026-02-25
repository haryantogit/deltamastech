<x-filament-panels::page>
    <style>
        .outlet-banner {
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .outlet-banner-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .outlet-banner-icon {
            width: 44px;
            height: 44px;
            background: rgba(251, 191, 36, 0.15);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .outlet-banner-icon svg {
            width: 22px;
            height: 22px;
            color: #fbbf24;
        }

        .outlet-banner-text h2 {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 4px 0;
        }

        .outlet-banner-text p {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
        }

        .outlet-banner-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .outlet-banner-btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .outlet-banner-btn svg {
            width: 16px;
            height: 16px;
        }

        .outlet-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 1200px) {
            .outlet-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .outlet-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .outlet-grid {
                grid-template-columns: 1fr;
            }
        }

        .outlet-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .outlet-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.12);
            transform: translateY(-2px);
        }

        .outlet-card-img {
            width: 100%;
            aspect-ratio: 16/10;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .outlet-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .outlet-card-img svg {
            width: 36px;
            height: 36px;
            color: #cbd5e1;
        }

        .outlet-card-body {
            padding: 14px 16px;
        }

        .outlet-card-name {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 2px 0;
        }

        .outlet-card-code {
            font-size: 11px;
            color: #94a3b8;
            margin: 0;
        }

        .outlet-card-actions {
            display: flex;
            border-top: 1px solid #f1f5f9;
        }

        .outlet-card-action {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            background: none;
            cursor: pointer;
            transition: background 0.15s;
        }

        .outlet-card-action svg {
            width: 14px;
            height: 14px;
        }

        .outlet-action-edit {
            color: #64748b;
        }

        .outlet-action-edit:hover {
            background: #f8fafc;
            color: #3b82f6;
        }

        .outlet-card-sep {
            width: 1px;
            background: #f1f5f9;
        }

        .outlet-action-delete {
            color: #ef4444;
        }

        .outlet-action-delete:hover {
            background: #fef2f2;
        }

        .outlet-empty {
            grid-column: 1 / -1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            border: 2px dashed #334155;
            border-radius: 16px;
            text-align: center;
        }

        .outlet-empty-icon {
            width: 56px;
            height: 56px;
            background: #1e293b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .outlet-empty-icon svg {
            width: 28px;
            height: 28px;
            color: #64748b;
        }

        .outlet-empty p {
            font-size: 13px;
            color: #64748b;
        }

        /* Dark mode */
        .dark .outlet-card {
            background: #1e293b;
            border-color: #334155;
        }

        .dark .outlet-card:hover {
            border-color: #3b82f6;
        }

        .dark .outlet-card-img {
            background: #0f172a;
        }

        .dark .outlet-card-name {
            color: #f1f5f9;
        }

        .dark .outlet-card-actions {
            border-color: #334155;
        }

        .dark .outlet-card-sep {
            background: #334155;
        }

        .dark .outlet-action-edit {
            color: #94a3b8;
        }

        .dark .outlet-action-edit:hover {
            background: rgba(59, 130, 246, 0.08);
            color: #60a5fa;
        }

        .dark .outlet-action-delete:hover {
            background: rgba(239, 68, 68, 0.08);
        }
    </style>

    {{-- Banner Header with Tambah Button --}}
    <div class="outlet-banner">
        <div class="outlet-banner-left">
            <div class="outlet-banner-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div class="outlet-banner-text">
                <h2>Outlet</h2>
                <p>Kelola outlet toko untuk POS Kasir</p>
            </div>
        </div>
        {{-- Button removed as it was redundant with header action --}}
    </div>

    {{-- Grid --}}
    <div class="outlet-grid">
        @forelse ($records as $record)
            <div class="outlet-card">
                <div class="outlet-card-img">
                    @if ($record->image)
                        <img src="{{ Storage::disk('public')->url($record->image) }}" alt="{{ $record->name }}">
                    @else
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    @endif
                </div>
                <div class="outlet-card-body">
                    <p class="outlet-card-name">{{ $record->name }}</p>
                    <p class="outlet-card-code">{{ $record->code }}</p>
                </div>
                <div class="outlet-card-actions">
                    <a href="{{ \App\Filament\Resources\OutletResource::getUrl('edit', ['record' => $record]) }}"
                        class="outlet-card-action outlet-action-edit">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Ubah
                    </a>
                    <div class="outlet-card-sep"></div>
                    <a href="{{ \App\Filament\Resources\OutletResource::getUrl('edit', ['record' => $record]) }}"
                        class="outlet-card-action outlet-action-delete">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Hapus
                    </a>
                </div>
            </div>
        @empty
            <div class="outlet-empty">
                <div class="outlet-empty-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <p>Belum ada outlet yang ditambahkan.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>