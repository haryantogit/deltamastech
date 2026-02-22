@php
    $record = $getRecord();
    $upgrades = $record->fixedAssetUpgrades()->get();
@endphp

<div
    style="border-radius: 12px; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; margin-top: 4px;">
    <div style="overflow-x: auto;">
        <table style="width: 100%; min-width: 750px; border-collapse: collapse; table-layout: fixed;">
            <colgroup>
                <col style="width: 110px">
                <col style="width: 110px">
                <col>
                <col style="width: 140px">
                <col style="width: 140px">
                <col style="width: 100px">
            </colgroup>
            <thead>
                <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                    <th
                        style="padding: 10px 12px 10px 24px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Tanggal
                    </th>
                    <th
                        style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Sumber
                    </th>
                    <th
                        style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Deskripsi
                    </th>
                    <th
                        style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Referensi
                    </th>
                    <th
                        style="padding: 10px 12px; text-align: right; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Jumlah
                    </th>
                    <th
                        style="padding: 10px 24px 10px 12px; text-align: right; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280;">
                        Bukti
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($upgrades as $upgrade)
                    <tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.15s;"
                        onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                        <td style="padding: 12px 12px 12px 24px; font-size: 13px; color: #4b5563; white-space: nowrap;">
                            {{ $upgrade->date->format('d/m/Y') }}
                        </td>
                        <td style="padding: 12px; white-space: nowrap;">
                            <span
                                style="display: inline-flex; align-items: center; border-radius: 6px; padding: 3px 8px; font-size: 11px; font-weight: 600; background: #d1fae5; color: #065f46; border: 1px solid rgba(16,185,129,0.2);">
                                Upgrade
                            </span>
                        </td>
                        <td style="padding: 12px; font-size: 13px; color: #4b5563;">
                            {{ $upgrade->description ?? 'Upgrade Aset Tetap' }}
                        </td>
                        <td style="padding: 12px; font-size: 13px; white-space: nowrap;">
                            @if($upgrade->reference)
                                <span style="font-weight: 500; color: #2563eb;">{{ $upgrade->reference }}</span>
                            @else
                                <span style="color: #d1d5db;">—</span>
                            @endif
                        </td>
                        <td
                            style="padding: 12px; font-size: 13px; font-weight: 600; text-align: right; color: #111827; white-space: nowrap; font-variant-numeric: tabular-nums;">
                            Rp {{ number_format($upgrade->amount, 0, ',', '.') }}
                        </td>
                        <td style="padding: 12px 24px 12px 12px; text-align: right; white-space: nowrap;">
                            @if($upgrade->evidence_image)
                                <a href="{{ asset('storage/' . $upgrade->evidence_image) }}" target="_blank"
                                    style="display: inline-flex; align-items: center; gap: 6px; border-radius: 8px; padding: 6px 12px; font-size: 12px; font-weight: 500; color: #374151; background: #f9fafb; border: 1px solid #d1d5db; text-decoration: none; transition: background 0.15s;"
                                    onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#f9fafb'">
                                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat
                                </a>
                            @else
                                <span style="color: #d1d5db;">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="padding: 48px 24px; text-align: center;">
                            <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                <svg style="width: 32px; height: 32px; color: #d1d5db;" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p style="font-size: 13px; color: #9ca3af; margin: 0;">Belum ada data upgrade aset</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>