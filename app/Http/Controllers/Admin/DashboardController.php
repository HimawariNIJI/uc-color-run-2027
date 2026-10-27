<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Date range for the trend chart (7, 14 or 30 days)
        $range = in_array((int) $request->query('range'), [7, 14, 30], true)
            ? (int) $request->query('range')
            : 7;

        // ---------------------------------------------------------------
        // SAMPLE DATA - replace each value with a real query, for example:
        //   $totalUsers          = User::whereNotNull('last_login_at')->count();
        //   $totalRegistrations  = Registration::count();
        //   $paidRegistrations   = Registration::where('payment_status', 'paid')->count();
        //   $bibIssued           = Registration::whereNotNull('bib_number')->count();
        // ---------------------------------------------------------------
        $totalUsers         = 2480;
        $totalRegistrations = 1860;
        $paidRegistrations  = 1425;
        $bibIssued          = 1280;

        $stats = [
            'total_users'         => $totalUsers,
            'total_registrations' => $totalRegistrations,
            'registration_rate'   => $totalUsers > 0 ? round($totalRegistrations / $totalUsers * 100, 1) : 0,
            'paid_registrations'  => $paidRegistrations,
            'paid_rate'           => $totalRegistrations > 0 ? round($paidRegistrations / $totalRegistrations * 100, 1) : 0,
            'bib_issued'          => $bibIssued,
            'awaiting_bib'        => max($paidRegistrations - $bibIssued, 0),
        ];

        // Bar chart: registrations by category
        // Real data: Registration::selectRaw('category, COUNT(*) total')->groupBy('category')->pluck('total', 'category');
        $categoryChart = [
            'labels' => ['3K Fun Run', '5K', '10K'],
            'data'   => [520, 900, 440],
        ];

        // Pie chart: payment status
        // Real data: Registration::selectRaw('payment_status, COUNT(*) total')->groupBy('payment_status')->pluck('total', 'payment_status');
        $paymentChart = [
            'labels' => ['Paid', 'Pending', 'Failed'],
            'data'   => [1425, 310, 125],
        ];

        // Line chart: new registrations per day
        // Real data:
        //   Registration::selectRaw('DATE(created_at) as day, COUNT(*) as total')
        //       ->where('created_at', '>=', now()->subDays($range - 1)->startOfDay())
        //       ->groupBy('day')->pluck('total', 'day');
        $labels = [];
        $values = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->format('d M');
            $values[] = 45 + ((($range - $i) * 17) % 40); // placeholder numbers
        }
        $trendChart = ['labels' => $labels, 'data' => $values];

        // Recent registrations (e.g. Registration::with('user')->latest()->limit(10)->get())
        $recent = [
            ['name' => 'Andi Pratama',  'email' => 'andi@example.com',  'reg_id' => 'UCR-2027-0125', 'category' => '5K',         'status' => 'paid',    'date' => '21 Oct 2026'],
            ['name' => 'Siti Rahayu',   'email' => 'siti@example.com',  'reg_id' => 'UCR-2027-0124', 'category' => '10K',        'status' => 'paid',    'date' => '21 Oct 2026'],
            ['name' => 'Budi Santoso',  'email' => 'budi@example.com',  'reg_id' => 'UCR-2027-0123', 'category' => '3K Fun Run', 'status' => 'pending', 'date' => '20 Oct 2026'],
            ['name' => 'Maria Gunawan', 'email' => 'maria@example.com', 'reg_id' => 'UCR-2027-0122', 'category' => '5K',         'status' => 'paid',    'date' => '20 Oct 2026'],
            ['name' => 'Kevin Wijaya',  'email' => 'kevin@example.com', 'reg_id' => 'UCR-2027-0121', 'category' => '10K',        'status' => 'failed',  'date' => '19 Oct 2026'],
        ];

        return view('admin.dashboard', compact(
            'stats', 'categoryChart', 'paymentChart', 'trendChart', 'recent', 'range'
        ));
    }
}