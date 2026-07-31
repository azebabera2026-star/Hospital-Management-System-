<?php

namespace Nucleus;

require_once __DIR__ . '/MessageTemplates.php';

class NotificationService
{
    private string $accountSid;
    private string $authToken;
    private string $fromNumber;
    private string $whatsappFrom;
    private bool $enabled;

    public function __construct()
    {
        $this->accountSid   = getenv('TWILIO_ACCOUNT_SID') ?: '';
        $this->authToken    = getenv('TWILIO_AUTH_TOKEN') ?: '';
        $this->fromNumber   = getenv('TWILIO_FROM_NUMBER') ?: '';
        $this->whatsappFrom = getenv('TWILIO_WHATSAPP_FROM') ?: '';
        $enabledEnv         = getenv('TWILIO_ENABLED');
        $this->enabled      = ($enabledEnv === false || $enabledEnv === 'true' || $enabledEnv === '1');
    }

    /**
     * Send SMS and WhatsApp for new appointment.
     */
    public function sendAppointmentCreated(array $patient, array $appointment, array $doctor): void
    {
        if (!$this->enabled || empty($patient['phone'])) {
            return;
        }

        $message = MessageTemplates::appointmentCreated($patient, $appointment, $doctor);
        $this->dispatchAll($patient, $appointment, $message);
    }

    /**
     * Send SMS and WhatsApp for updated appointment.
     */
    public function sendAppointmentUpdated(array $patient, array $appointment, array $doctor, ?string $oldStatus = null): void
    {
        if (!$this->enabled || empty($patient['phone'])) {
            return;
        }

        $message = MessageTemplates::appointmentUpdated($patient, $appointment, $doctor, $oldStatus);
        $this->dispatchAll($patient, $appointment, $message);
    }

    /**
     * Send both SMS and WhatsApp channels. Fire-and-forget; log errors gracefully.
     */
    private function dispatchAll(array $patient, array $appointment, string $message): void
    {
        $toPhone = $patient['phone'] ?? '';
        if (!$toPhone) {
            return;
        }

        // Standardize phone number format if needed
        $cleanPhone = preg_replace('/[^\+0-9]/', '', $toPhone);

        // 1. Send SMS
        if (!empty($this->fromNumber)) {
            $this->sendTwilioMessage(
                $cleanPhone,
                $this->fromNumber,
                $message,
                'sms',
                (int)($patient['id'] ?? 0),
                (int)($appointment['id'] ?? 0)
            );
        }

        // 2. Send WhatsApp
        if (!empty($this->whatsappFrom)) {
            $waTo   = str_starts_with($cleanPhone, 'whatsapp:') ? $cleanPhone : 'whatsapp:' . $cleanPhone;
            $waFrom = str_starts_with($this->whatsappFrom, 'whatsapp:') ? $this->whatsappFrom : 'whatsapp:' . $this->whatsappFrom;

            $this->sendTwilioMessage(
                $waTo,
                $waFrom,
                $message,
                'whatsapp',
                (int)($patient['id'] ?? 0),
                (int)($appointment['id'] ?? 0)
            );
        }
    }

    /**
     * Executes HTTP REST request to Twilio API.
     */
    private function sendTwilioMessage(
        string $to,
        string $from,
        string $body,
        string $channel,
        int $patientId,
        int $appointmentId
    ): void {
        if (empty($this->accountSid) || empty($this->authToken)) {
            $this->logNotification($patientId, $appointmentId, $channel, 'failed', null, 'Twilio credentials not configured');
            return;
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
        $data = [
            'From' => $from,
            'To'   => $to,
            'Body' => $body,
        ];

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->accountSid}:{$this->authToken}");
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                $this->logNotification($patientId, $appointmentId, $channel, 'failed', null, "cURL Error: " . $curlErr);
                return;
            }

            $resData = json_decode($response, true);
            if ($httpCode >= 200 && $httpCode < 300 && !empty($resData['sid'])) {
                $this->logNotification($patientId, $appointmentId, $channel, 'sent', $resData['sid'], null);
            } else {
                $errMsg = $resData['message'] ?? ("HTTP " . $httpCode . ": " . $response);
                $this->logNotification($patientId, $appointmentId, $channel, 'failed', null, $errMsg);
            }
        } catch (\Throwable $e) {
            error_log("[Twilio Notification Error] " . $e->getMessage());
            $this->logNotification($patientId, $appointmentId, $channel, 'failed', null, $e->getMessage());
        }
    }

    /**
     * Record entry in notification_logs table if database connection is available.
     */
    private function logNotification(
        int $patientId,
        int $appointmentId,
        string $channel,
        string $status,
        ?string $messageSid,
        ?string $error
    ): void {
        try {
            if (function_exists('db')) {
                $stmt = \db()->prepare(
                    "INSERT INTO notification_logs (patient_id, appointment_id, channel, status, message_sid, error, sent_at)
                     VALUES (?, ?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([
                    $patientId ?: null,
                    $appointmentId ?: null,
                    $channel,
                    $status,
                    $messageSid,
                    $error
                ]);
            }
        } catch (\Throwable $ex) {
            error_log("[Notification Log Failure] " . $ex->getMessage());
        }
    }
}
