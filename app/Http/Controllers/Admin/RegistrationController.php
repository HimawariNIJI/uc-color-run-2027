<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    private function dummyRegistrations(): array
    {
        return [
            [
                'id' => 'REG-2027-001',
                'name' => 'Andi Pratama',
                'email' => 'andi@example.com',
                'category' => '10K',
                'payment_status' => 'Paid',
                'bib_number' => 'UC27001',
            ],
            [
                'id' => 'REG-2027-002',
                'name' => 'Jessica Tan',
                'email' => 'jessica@example.com',
                'category' => '5K',
                'payment_status' => 'Pending',
                'bib_number' => 'UC27002',
            ],
            [
                'id' => 'REG-2027-003',
                'name' => 'Budi Santoso',
                'email' => 'budi@example.com',
                'category' => 'Half Marathon',
                'payment_status' => 'Paid',
                'bib_number' => 'UC27003',
            ],
            [
                'id' => 'REG-2027-004',
                'name' => 'Clara Wijaya',
                'email' => 'clara@example.com',
                'category' => '10K',
                'payment_status' => 'Unpaid',
                'bib_number' => '-',
            ],
            [
                'id' => 'REG-2027-005',
                'name' => 'David Lim',
                'email' => 'david@example.com',
                'category' => '5K',
                'payment_status' => 'Paid',
                'bib_number' => 'UC27005',
            ],
            [
                'id' => 'REG-2027-006',
                'name' => 'Felicia Putri',
                'email' => 'felicia@example.com',
                'category' => 'Half Marathon',
                'payment_status' => 'Pending',
                'bib_number' => '-',
            ],
            [
                'id' => 'REG-2027-007',
                'name' => 'Kevin Hartono',
                'email' => 'kevin@example.com',
                'category' => '10K',
                'payment_status' => 'Paid',
                'bib_number' => 'UC27007',
            ],
            [
                'id' => 'REG-2027-008',
                'name' => 'Maria Natalia',
                'email' => 'maria@example.com',
                'category' => '5K',
                'payment_status' => 'Unpaid',
                'bib_number' => '-',
            ],
        ];
    }

    public function index()
    {
        $registrations = $this->dummyRegistrations();

        return view(
            'admin.registration',
            compact('registrations')
        );
    }

    public function export()
    {
        $registrations = $this->dummyRegistrations();

        $filename = 'participant-registrations.csv';

        return response()->streamDownload(function () use ($registrations) {
            $output = fopen('php://output', 'w');

            // BOM agar karakter CSV lebih kompatibel dengan Excel.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Registration ID',
                'Name',
                'Email',
                'Category',
                'Payment Status',
                'BIB Number',
            ]);

            foreach ($registrations as $registration) {
                fputcsv($output, [
                    $registration['id'],
                    $registration['name'],
                    $registration['email'],
                    $registration['category'],
                    $registration['payment_status'],
                    $registration['bib_number'],
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}