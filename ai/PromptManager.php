<?php
/**
 * PromptManager.php — System Prompt Builder
 * Injects hospital context dynamically into AI system prompts.
 */

namespace Nucleus\AI;

class PromptManager
{
    /** Build the full system prompt with live hospital context injected. */
    public static function buildSystemPrompt(array $context = [], string $lang = 'en'): string
    {
        $today   = date('l, F j, Y');
        $hospCtx = self::buildHospitalContext($context);

        $base = <<<PROMPT
You are Nucleus AI — an intelligent assistant embedded inside the Nucleus Hospital Management System (HMS) in Addis Ababa, Ethiopia.

TODAY'S DATE: {$today}

YOUR ROLE:
- Help administrators, doctors, nurses, and staff manage the hospital efficiently.
- Answer medical and clinical questions within the hospital context.
- Search and summarize patient, doctor, appointment, treatment, billing, and room data.
- Generate professional summaries, reports, and recommendations.
- Draft appointment reminders for SMS/WhatsApp.
- Always be concise, accurate, and professional.

HOSPITAL CONTEXT:
{$hospCtx}

RESPONSE RULES:
1. Always respond using the provided hospital data when the query relates to a specific patient, doctor, or appointment.
2. Format responses clearly using markdown: **bold**, bullet lists, tables where appropriate.
3. When generating medical summaries, include: Diagnosis, Medications, Doctor, Last Visit.
4. For billing queries, show: Invoice #, Patient, Amount, Status.
5. For appointment queries, show: Patient, Doctor, Date/Time, Status.
6. If you cannot find specific data, say so clearly and offer to help differently.
7. Never fabricate patient records — always work from the provided context.
8. When suggesting actions, be specific (e.g., "Schedule appointment with Dr. Dawit Solomon in Cardiology").
9. Keep responses focused and professional — this is a clinical environment.

LANGUAGE: {$lang}
AMHARIC SUPPORT: If the user writes in Amharic, respond in Amharic.
PROMPT;

        return $base;
    }

    /** Serialize hospital data for context injection. */
    private static function buildHospitalContext(array $ctx): string
    {
        $parts = [];

        if (!empty($ctx['patients'])) {
            $parts[] = "PATIENTS (" . count($ctx['patients']) . " total):";
            foreach (array_slice($ctx['patients'], 0, 10) as $p) {
                $parts[] = "  - #{$p['id']}: {$p['first_name']} {$p['last_name']}, {$p['gender']}, Phone: {$p['phone']}, Address: {$p['address']}";
            }
        }

        if (!empty($ctx['doctors'])) {
            $parts[] = "\nDOCTORS (" . count($ctx['doctors']) . " total):";
            foreach ($ctx['doctors'] as $d) {
                $parts[] = "  - #{$d['id']}: Dr. {$d['first_name']} {$d['last_name']}, {$d['specialization']}, Dept #{$d['department_id']}";
            }
        }

        if (!empty($ctx['appointments'])) {
            $parts[] = "\nAPPOINTMENTS (" . count($ctx['appointments']) . " total):";
            foreach (array_slice($ctx['appointments'], 0, 10) as $a) {
                $status  = $a['status'] ?? 'unknown';
                $parts[] = "  - Appt #{$a['id']}: Patient #{$a['patient_id']} with Doctor #{$a['doctor_id']} on {$a['appointment_date']} [{$status}]";
            }
        }

        if (!empty($ctx['rooms'])) {
            $available = array_filter($ctx['rooms'], fn($r) => $r['status'] === 'available');
            $occupied  = array_filter($ctx['rooms'], fn($r) => $r['status'] === 'occupied');
            $parts[]   = "\nROOMS: " . count($available) . " available, " . count($occupied) . " occupied out of " . count($ctx['rooms']) . " total";
        }

        if (!empty($ctx['invoices'])) {
            $unpaid    = array_filter($ctx['invoices'], fn($i) => $i['status'] === 'unpaid');
            $totalPaid = array_sum(array_column(array_filter($ctx['invoices'], fn($i) => $i['status'] === 'paid'), 'paid_amount'));
            $parts[]   = "\nBILLING: " . count($unpaid) . " unpaid invoices, ETB {$totalPaid} collected total";
        }

        if (!empty($ctx['departments'])) {
            $parts[] = "\nDEPARTMENTS: " . implode(', ', array_column($ctx['departments'], 'name'));
        }

        if (!empty($ctx['medications'])) {
            $parts[] = "\nMEDICATIONS IN FORMULARY: " . count($ctx['medications']) . " items";
        }

        return implode("\n", $parts) ?: 'No hospital data provided.';
    }

    /** Build a targeted prompt for a specific query type. */
    public static function buildQueryPrompt(string $queryType, array $data): string
    {
        return match($queryType) {
            'patient_summary' => "Generate a comprehensive medical summary for patient: " . json_encode($data),
            'billing_report'  => "Generate a billing report for: " . json_encode($data),
            'daily_report'    => "Generate a professional hospital daily report for today based on: " . json_encode($data),
            'appointment_reminder' => "Draft a polite SMS and WhatsApp appointment reminder for: " . json_encode($data),
            'room_status'     => "Summarize the current room occupancy status: " . json_encode($data),
            default           => json_encode($data),
        };
    }
}
