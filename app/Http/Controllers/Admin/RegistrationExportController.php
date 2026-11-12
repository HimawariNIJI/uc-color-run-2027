<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Support\CsvExport;
use App\Support\RegistrationFilters;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationExportController extends Controller
{
    /**
     * Download the registrations matching the current filters as CSV.
     *
     * KTP/Passport numbers, KTP photos and medical information (Riwayat Penyakit, Alergi Obat)
     * are left out on purpose: exported files tend to be forwarded.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $registrations = RegistrationFilters::fromRequest($request)
            ->apply(Registration::query())
            ->lazyById(500);

        return CsvExport::download(
            CsvExport::filename('registrations'),
            [
                'Registration ID', 'Registration Type', 'Race Category', 'Full Nama (Nama Pembayar)', 'Email Address', 'Phone Number',
                'Date of Birth', 'Gender', 'T-shirt Size', 'Emergency Contact Name', 'Emergency Contact Phone',
                'Province', 'City', 'Full Address', 'Price Type', 'Amount', 'Payment Status', 'Registration Date',
                'Payment Date', 'Transaction ID', 'BIB Number', 'BIB Collected At',
            ],
            $registrations,
            fn (Registration $registration): array => [
                $registration->code,
                $registration->type->label(),
                $registration->category->label(),
                CsvExport::text($registration->full_name),
                CsvExport::text($registration->email),
                $registration->phone,
                $registration->birth_date->format('Y-m-d'),
                $registration->gender->label(),
                $registration->tshirt_size->value,
                CsvExport::text($registration->emergency_contact_name),
                $registration->emergency_contact_phone,
                $registration->province,
                $registration->city,
                CsvExport::text($registration->address),
                $registration->fee_period?->label() ?? $registration->packageName(),
                $registration->amount,
                $registration->payment_status->label(),
                $registration->created_at->format('Y-m-d H:i'),
                $registration->paid_at?->format('Y-m-d H:i'),
                $registration->transaction_id,
                CsvExport::digits($registration->bib_number),
                $registration->bib_collected_at?->format('Y-m-d H:i'),
            ],
        );
    }
}
