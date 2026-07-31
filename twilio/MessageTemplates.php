<?php

namespace Nucleus;

class MessageTemplates
{
    /**
     * Format message for newly scheduled appointment.
     */
    public static function appointmentCreated(array $patient, array $appointment, array $doctor): string
    {
        $patientName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
        $doctorName  = trim(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? ''));
        $dateFormatted = !empty($appointment['appointment_date']) 
            ? date('D, d M Y \a\t g:i A', strtotime($appointment['appointment_date']))
            : ($appointment['appointment_date'] ?? 'N/A');

        return "📅 Nucleus Hospital – Appointment Confirmed\n"
            . "Dear {$patientName}, your appointment with Dr. {$doctorName} is scheduled for {$dateFormatted}.\n"
            . "Reference #: " . ($appointment['id'] ?? 'N/A') . ". For inquiries, call +251-11-661-0000.";
    }

    /**
     * Format message for updated appointment status/details.
     */
    public static function appointmentUpdated(array $patient, array $appointment, array $doctor, ?string $oldStatus = null): string
    {
        $patientName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
        $doctorName  = trim(($doctor['first_name'] ?? '') . ' ' . ($doctor['last_name'] ?? ''));
        $status      = ucfirst($appointment['status'] ?? 'Scheduled');
        $dateFormatted = !empty($appointment['appointment_date']) 
            ? date('D, d M Y \a\t g:i A', strtotime($appointment['appointment_date']))
            : ($appointment['appointment_date'] ?? 'N/A');

        $msg = "🔔 Nucleus Hospital – Appointment Update\n"
            . "Dear {$patientName}, your appointment (#" . ($appointment['id'] ?? 'N/A') . ") status is now: {$status}.\n"
            . "Doctor: Dr. {$doctorName} | Date: {$dateFormatted}.";

        if (!empty($appointment['notes'])) {
            $msg .= "\nNotes: " . $appointment['notes'];
        }

        return $msg;
    }
}
