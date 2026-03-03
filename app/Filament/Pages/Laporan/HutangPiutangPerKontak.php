<?php

namespace App\Filament\Pages\Laporan;

use App\Models\Contact;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms; // Corrected namespace
use Livewire\WithPagination;

class HutangPiutangPerKontak extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    public $filters = [];
    public $search = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    protected string $view = 'filament.pages.laporan.hutang-piutang-per-kontak';

    protected static ?string $title = 'Hutang Piutang per Kontak';
    protected static ?string $slug = 'hutang-piutang-per-kontak';
    protected static bool $shouldRegisterNavigation = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\ReportPage::getUrl() => 'Laporan',
            'Hutang Piutang per Kontak',
        ];
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function mount(): void
    {
        $this->filters = [
            'startDate' => now()->startOfYear()->toDateString(),
            'endDate' => now()->toDateString(),
        ];
    }

    public function getSubheading(): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $startFmt = Carbon::parse($startDate)->format('d/m/Y');
        $endFmt = Carbon::parse($endDate)->format('d/m/Y');

        $dateDisplay = $startFmt === $endFmt
            ? $startFmt
            : $startFmt . ' &mdash; ' . $endFmt;

        return new \Illuminate\Support\HtmlString('
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background-color: #f8fafc; padding: 0.5rem 1rem; border-radius: 0.5rem; border: 1px solid #e2e8f0; font-size: 0.875rem; font-weight: 600; color: #475569;" class="dark:bg-white/5 dark:border-white/10 dark:text-gray-300">
                <svg style="width: 1.25rem; height: 1.25rem; opacity: 0.7;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>' . $dateDisplay . '</span>
            </div>
        ');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('filter')
                ->label('Filter')
                ->icon('heroicon-m-funnel')
                ->color('gray')
                ->form([
                    DatePicker::make('startDate')
                        ->label('Tanggal Mulai')
                        ->default($this->filters['startDate'])
                        ->required(),
                    DatePicker::make('endDate')
                        ->label('Tanggal Akhir')
                        ->default($this->filters['endDate'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->filters['startDate'] = $data['startDate'];
                    $this->filters['endDate'] = $data['endDate'];
                    $this->resetPage();
                }),
            Action::make('print')
                ->label('Print')
                ->color('gray')
                ->icon('heroicon-o-printer')
                ->action(fn() => $this->js('window.print()')),
            Action::make('kembali')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(\App\Filament\Pages\ReportPage::getUrl()),
        ];
    }

    public function getViewData(): array
    {
        $startDate = $this->filters['startDate'] ?? now()->startOfYear()->toDateString();
        $endDate = $this->filters['endDate'] ?? now()->toDateString();
        $today = Carbon::now()->format('d/m/Y');

        // Get invoice numbers to exclude (those created from sales/purchase invoices)
        $purchaseInvoiceNumbers = \App\Models\PurchaseInvoice::pluck('number')->toArray();
        $salesInvoiceNumbers = \App\Models\SalesInvoice::pluck('invoice_number')->toArray();

        // Get all contacts that have debts or receivables (excluding invoice-generated ones)
        $contactsWithDebt = Debt::whereNotIn('reference', $purchaseInvoiceNumbers)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('supplier_id')
            ->groupBy('supplier_id')
            ->pluck('supplier_id')
            ->toArray();

        $contactsWithReceivable = Receivable::whereNotIn('invoice_number', $salesInvoiceNumbers)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select('contact_id')
            ->groupBy('contact_id')
            ->pluck('contact_id')
            ->toArray();

        $allContactIds = array_unique(array_merge($contactsWithDebt, $contactsWithReceivable));

        // 1. Calculate Grand Totals on ALL filtered ids
        $totalHutang = 0;
        $totalPiutang = 0;

        $allFilteredContacts = Contact::whereIn('id', $allContactIds)
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->get();

        foreach ($allFilteredContacts as $contact) {
            $h = Debt::where('supplier_id', $contact->id)
                ->whereNotIn('reference', $purchaseInvoiceNumbers)
                ->whereBetween('date', [$startDate, $endDate])
                ->sum('total_amount');
            $debtIds = Debt::where('supplier_id', $contact->id)
                ->whereNotIn('reference', $purchaseInvoiceNumbers)
                ->whereBetween('date', [$startDate, $endDate])
                ->pluck('id');
            $hPaid = DebtPayment::whereIn('debt_id', $debtIds)->sum('amount');

            $totalHutang += ($h - $hPaid);

            $p = Receivable::where('contact_id', $contact->id)
                ->whereNotIn('invoice_number', $salesInvoiceNumbers)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->sum('total_amount');
            $receivableIds = Receivable::where('contact_id', $contact->id)
                ->whereNotIn('invoice_number', $salesInvoiceNumbers)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->pluck('id');
            $pPaid = ReceivablePayment::whereIn('receivable_id', $receivableIds)->sum('amount');

            $totalPiutang += ($p - $pPaid);
        }

        // 2. Prepare paginated rows
        $contactsQuery = Contact::whereIn('id', $allContactIds)
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name');

        $perPageCount = $this->perPage === 'all' ? max(1, (clone $contactsQuery)->count()) : $this->perPage;
        $paginatedContacts = $contactsQuery->paginate($perPageCount);
        $rows = [];

        foreach ($paginatedContacts as $contact) {
            // --- HUTANG ---
            $debts = Debt::where('supplier_id', $contact->id)
                ->whereNotIn('reference', $purchaseInvoiceNumbers)
                ->whereBetween('date', [$startDate, $endDate])
                ->with('payments')
                ->get();
            $hutangTotal = 0;
            $debtDetails = [];

            foreach ($debts as $debt) {
                $paid = $debt->payments->sum('amount');
                $outstanding = (float) $debt->total_amount - (float) $paid;
                if (abs($outstanding) < 0.01)
                    continue;

                $hutangTotal += $outstanding;
                $debtDetails[] = [
                    'tanggal' => $debt->date ? Carbon::parse($debt->date)->format('d/m/Y') : '-',
                    'nomor' => $debt->number ?? '-',
                    'deskripsi' => $debt->reference ?? $debt->notes ?? '-',
                    'hutang' => $outstanding,
                    'piutang' => 0,
                    'net' => -$outstanding,
                ];
            }

            // --- PIUTANG ---
            $receivables = Receivable::where('contact_id', $contact->id)
                ->whereNotIn('invoice_number', $salesInvoiceNumbers)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->with('payments')
                ->get();
            $piutangTotal = 0;
            $receivableDetails = [];

            foreach ($receivables as $receivable) {
                $paid = $receivable->payments->sum('amount');
                $outstanding = (float) $receivable->total_amount - (float) $paid;
                if (abs($outstanding) < 0.01)
                    continue;

                $piutangTotal += $outstanding;
                $receivableDetails[] = [
                    'tanggal' => $receivable->transaction_date ? Carbon::parse($receivable->transaction_date)->format('d/m/Y') : '-',
                    'nomor' => $receivable->invoice_number ?? '-',
                    'deskripsi' => $receivable->reference ?? $receivable->notes ?? '-',
                    'hutang' => 0,
                    'piutang' => $outstanding,
                    'net' => $outstanding,
                ];
            }

            if ($hutangTotal == 0 && $piutangTotal == 0)
                continue;

            $details = array_merge($debtDetails, $receivableDetails);
            usort($details, function ($a, $b) {
                $da = Carbon::createFromFormat('d/m/Y', $a['tanggal']);
                $db = Carbon::createFromFormat('d/m/Y', $b['tanggal']);
                return $da->timestamp <=> $db->timestamp;
            });

            $rows[] = [
                'contact' => $contact->name,
                'hutang' => $hutangTotal,
                'piutang' => $piutangTotal,
                'net' => $piutangTotal - $hutangTotal,
                'details' => $details,
            ];
        }

        return [
            'today' => $today,
            'startDate' => Carbon::parse($startDate)->format('d/m/Y'),
            'endDate' => Carbon::parse($endDate)->format('d/m/Y'),
            'rows' => $rows,
            'paginator' => $paginatedContacts,
            'totalHutang' => $totalHutang,
            'totalPiutang' => $totalPiutang,
            'netTotal' => $totalPiutang - $totalHutang,
        ];
    }
}
