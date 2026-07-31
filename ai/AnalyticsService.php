<?php
/**
 * AnalyticsService.php — Hospital Analytics & Reporting Engine
 * Generates structured performance metrics, occupancy rates, and revenue statistics.
 */

namespace Nucleus\AI;

class AnalyticsService
{
    /**
     * Generate daily, weekly, or monthly analytical report data.
     */
    public static function generateReport(string $type = 'daily', array $dbData = []): array
    {
        $patients     = $dbData['patients'] ?? [];
        $appointments = $dbData['appointments'] ?? [];
        $rooms        = $dbData['rooms'] ?? [];
        $admissions   = $dbData['admissions'] ?? [];
        $invoices     = $dbData['invoices'] ?? [];

        // Occupancy calculation
        $totalRooms    = count($rooms) ?: 11;
        $occupiedRooms = count(array_filter($rooms, fn($r) => ($r['status'] ?? '') === 'occupied'));
        $occupancyRate = round(($occupiedRooms / $totalRooms) * 100, 1);

        // Revenue stats
        $totalRevenue  = array_sum(array_column(array_filter($invoices, fn($i) => ($i['status'] ?? '') === 'paid'), 'paid_amount'));
        $unpaidInvoices = count(array_filter($invoices, fn($i) => ($i['status'] ?? '') === 'unpaid'));

        // Appointment breakdown
        $scheduled   = count(array_filter($appointments, fn($a) => ($a['status'] ?? '') === 'scheduled'));
        $completed   = count(array_filter($appointments, fn($a) => ($a['status'] ?? '') === 'completed'));
        $cancelled   = count(array_filter($appointments, fn($a) => ($a['status'] ?? '') === 'cancelled'));

        return [
            'type'            => ucfirst($type) . ' Hospital Report',
            'generated_at'    => date('Y-m-d H:i:s'),
            'occupancy_rate'  => $occupancyRate . '%',
            'occupied_rooms'  => $occupiedRooms,
            'total_rooms'     => $totalRooms,
            'total_patients'  => count($patients),
            'appointments'    => [
                'total'     => count($appointments),
                'scheduled' => $scheduled,
                'completed' => $completed,
                'cancelled' => $cancelled,
            ],
            'financials'      => [
                'total_collected_etb' => $totalRevenue,
                'unpaid_count'       => $unpaidInvoices,
            ],
            'active_admissions' => count(array_filter($admissions, fn($a) => empty($a['discharged_on']))),
        ];
    }
}
