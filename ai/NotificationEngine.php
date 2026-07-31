<?php
/**
 * NotificationEngine.php — AI-driven Notification Drafts
 * Automatically prepares SMS/WhatsApp drafts for patients or doctors.
 */

namespace Nucleus\AI;

class NotificationEngine
{
    /**
     * Generate an SMS/WhatsApp draft using AI or fallback templates.
     * In a production environment, this could hook into Twilio/WhatsApp API.
     */
    public static function draftReminder(array $patient, array $appointment, array $doctor): array
    {
        $patientName = $patient['first_name'] . ' ' . $patient['last_name'];
        $doctorName  = 'Dr. ' . $doctor['first_name'] . ' ' . $doctor['last_name'];
        $date        = date('l, F j, Y', strtotime($appointment['appointment_date']));
        $time        = date('h:i A', strtotime($appointment['appointment_date']));

        $english = "Hello {$patientName}, this is a reminder for your appointment at Nucleus Hospital with {$doctorName} on {$date} at {$time}. Please arrive 15 minutes early.";
        $amharic = "ጤና ይስጥልኝ {$patientName}፣ ይህ በኒውክሊየስ ሆስፒታል ከ{$doctorName} ጋር በ{$date} ከቀኑ {$time} ላሎት ቀጠሮ ማስታወሻ ነው። እባክዎ 15 ደቂቃ ቀደም ብለው ይገኙ።";

        return [
            'type'        => 'Appointment Reminder',
            'patient_id'  => $patient['id'],
            'phone'       => $patient['phone'] ?? 'Unknown',
            'drafts'      => [
                'en' => $english,
                'am' => $amharic,
            ],
            'whatsapp_link' => 'https://wa.me/' . preg_replace('/[^0-9]/', '', $patient['phone'] ?? ''),
        ];
    }
}
