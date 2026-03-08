<?php

namespace App\Http\Controllers;

use App\Models\SalesQuotation;
use App\Models\PurchaseQuote;
use App\Models\SalesInvoice;
use App\Models\PurchaseInvoice;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\SalesDelivery;
use App\Models\PurchaseDelivery;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Company;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function printQuotation($id)
    {
        $record = SalesQuotation::with(['contact', 'items.product', 'items.unit'])->findOrFail($id);
        $company = Company::first();
        $title = 'Penawaran Penjualan';
        $type = 'sales';

        return view('print.quotation', compact('record', 'company', 'title', 'type'));
    }

    public function printPurchaseQuote($id)
    {
        $record = PurchaseQuote::with(['supplier', 'items.product', 'items.unit'])->findOrFail($id);
        $company = Company::first();
        $title = 'Penawaran Pembelian';
        $type = 'purchase';

        return view('print.quotation', compact('record', 'company', 'title', 'type'));
    }

    public function printInvoice($id)
    {
        $record = SalesInvoice::with(['contact', 'items.product', 'items.unit', 'paymentTerm', 'salesOrder'])->findOrFail($id);
        $company = Company::first();

        $receivable = Receivable::where('invoice_number', $record->invoice_number)->first();
        $totalPaid = ($receivable ? $receivable->payments()->sum('amount') : 0) + ($record->down_payment ?? 0);
        $balanceDue = ($record->total_amount ?? 0) - $totalPaid;
        if ($record->status === 'paid') {
            $balanceDue = 0;
        }
        if ($balanceDue < 0) {
            $balanceDue = 0;
        }

        // Get linked delivery
        $delivery = null;
        if ($record->salesOrder) {
            $delivery = SalesDelivery::where('sales_order_id', $record->salesOrder->id)->first();
        }

        return view('print.invoice', compact('record', 'company', 'balanceDue', 'delivery'));
    }

    public function printSuratJalan($id)
    {
        $record = SalesInvoice::with(['contact', 'items.product', 'items.unit', 'salesOrder'])->findOrFail($id);
        $company = Company::first();

        // Get linked delivery
        $delivery = null;
        if ($record->salesOrder) {
            $delivery = SalesDelivery::with(['items.product', 'items.unit', 'shippingMethod'])
                ->where('sales_order_id', $record->salesOrder->id)
                ->first();
        }

        return view('print.surat-jalan', compact('record', 'company', 'delivery'));
    }

    public function printKwitansi($id)
    {
        $record = SalesInvoice::with(['contact', 'paymentTerm'])->findOrFail($id);
        $company = Company::first();

        $receivable = Receivable::where('invoice_number', $record->invoice_number)->first();
        $totalPaid = ($receivable ? $receivable->payments()->sum('amount') : 0) + ($record->down_payment ?? 0);
        $balanceDue = ($record->total_amount ?? 0) - $totalPaid;
        if ($record->status === 'paid') {
            $balanceDue = 0;
        }
        if ($balanceDue < 0) {
            $balanceDue = 0;
        }

        // Get last payment info
        $lastPayment = null;
        if ($receivable) {
            $lastPayment = $receivable->payments()->with('account')->latest()->first();
        }

        return view('print.kwitansi', compact('record', 'company', 'totalPaid', 'balanceDue', 'lastPayment', 'receivable'));
    }

    public function printLabelPengiriman($id)
    {
        $record = SalesInvoice::with(['contact', 'items.product', 'items.unit', 'salesOrder'])->findOrFail($id);
        $company = Company::first();

        // Get linked delivery
        $delivery = null;
        if ($record->salesOrder) {
            $delivery = SalesDelivery::with(['items.product', 'items.unit', 'shippingMethod'])
                ->where('sales_order_id', $record->salesOrder->id)
                ->first();
        }

        return view('print.label-pengiriman', compact('record', 'company', 'delivery'));
    }

    public function printSalesOrder($id)
    {
        $record = SalesOrder::with(['customer', 'items.product', 'items.unit', 'paymentTerm', 'warehouse'])->findOrFail($id);
        $company = Company::first();

        return view('print.sales-order', compact('record', 'company'));
    }

    public function printPurchaseOrder($id)
    {
        $record = PurchaseOrder::with(['supplier', 'items.product', 'items.unit', 'paymentTerm', 'warehouse'])->findOrFail($id);
        $company = Company::first();

        return view('print.purchase-order', compact('record', 'company'));
    }

    public function printDeliverySuratJalan($id)
    {
        $delivery = SalesDelivery::with(['items.product', 'items.unit', 'customer', 'salesOrder', 'shippingMethod'])->findOrFail($id);
        $company = Company::first();

        // Build a pseudo-record for the template
        $record = new \stdClass();
        $record->contact = $delivery->customer;
        $record->invoice_number = $delivery->number;
        $record->transaction_date = $delivery->date;
        $record->salesOrder = $delivery->salesOrder;
        $record->items = $delivery->items;

        return view('print.surat-jalan', compact('record', 'company', 'delivery'));
    }

    public function printDeliveryLabel($id)
    {
        $delivery = SalesDelivery::with(['items.product', 'items.unit', 'customer', 'salesOrder', 'shippingMethod'])->findOrFail($id);
        $company = Company::first();

        // Build a pseudo-record for the template
        $record = new \stdClass();
        $record->contact = $delivery->customer;
        $record->invoice_number = $delivery->number;
        $record->items = $delivery->items;
        $record->salesOrder = $delivery->salesOrder;

        return view('print.label-pengiriman', compact('record', 'company', 'delivery'));
    }

    public function printPurchaseInvoice($id)
    {
        $record = PurchaseInvoice::with(['supplier', 'items.product', 'items.unit', 'items.tax', 'paymentTerm', 'purchaseOrder'])->findOrFail($id);
        $company = Company::first();

        $debt = \App\Models\Debt::where('reference', $record->number)->first();
        $totalPaid = ($debt ? $debt->payments()->sum('amount') : 0) + ($record->down_payment ?? 0);
        $balanceDue = (float) ($record->total_amount - $totalPaid - ($record->withholding_amount ?? 0));
        if ($record->payment_status === 'paid' || $record->status === 'paid') {
            $balanceDue = 0;
        }
        if ($balanceDue < 0) {
            $balanceDue = 0;
        }

        return view('print.purchase-invoice', compact('record', 'company', 'totalPaid', 'balanceDue'));
    }

    public function printPurchaseDelivery($id)
    {
        $record = PurchaseDelivery::with(['supplier', 'items.product', 'items.unit', 'purchaseOrder', 'shippingMethod', 'warehouse'])->findOrFail($id);
        $company = Company::first();

        return view('print.purchase-delivery', compact('record', 'company'));
    }
}
