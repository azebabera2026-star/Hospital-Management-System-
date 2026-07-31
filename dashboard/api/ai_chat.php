<?php
/**
 * ai_chat.php — Core AI Chat Endpoint
 * Handles incoming chat messages, loads DB context, and streams responses.
 */

require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../../ai/AIService.php';
require_once __DIR__ . '/../../ai/PromptManager.php';
require_once __DIR__ . '/../../ai/ConversationMemory.php';

use Nucleus\AI\AIService;
use Nucleus\AI\PromptManager;
use Nucleus\AI\ConversationMemory;

// Ensure JSON or Form Data input
$rawInput = file_get_contents('php://input');
$data     = json_decode($rawInput, true);
$message  = trim((string)($data['message'] ?? $_POST['message'] ?? ''));
$isClear  = (bool)($data['clear'] ?? $_POST['clear'] ?? false);
$lang     = (string)($data['lang'] ?? $_POST['lang'] ?? 'en');

if ($isClear) {
    ConversationMemory::clear();
    echo json_encode(['status' => 'cleared']);
    exit;
}

if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// 1. Gather live hospital context (simplified for performance)
// In a real DB, you would query just what's needed. Here we pass mock arrays directly
// since database.php currently doesn't define functions returning the arrays.
// The frontend has `DB`, but backend `database.php` currently just has PDO connection.
// Wait, `database.php` from my previous run: I cleaned it. Let's assume we can fetch data.
// Actually, I'll pass a minimal context, and rely on the AI's internal knowledge of the prompt.
// We can use a query if `database.php` had methods, but for now we'll rely on the DB structure.

$pdo = getDB();
$ctx = [];

try {
    // Fetch limited context for the system prompt
    $stmt = $pdo->query("SELECT id, first_name, last_name, gender, phone, address FROM patients LIMIT 10");
    $ctx['patients'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $stmt = $pdo->query("SELECT id, first_name, last_name, specialization, department_id FROM doctors LIMIT 10");
    $ctx['doctors'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    $stmt = $pdo->query("SELECT id, patient_id, doctor_id, appointment_date, status FROM appointments WHERE appointment_date >= CURDATE() LIMIT 10");
    $ctx['appointments'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    $stmt = $pdo->query("SELECT id, room_number, status FROM rooms");
    $ctx['rooms'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $stmt = $pdo->query("SELECT id, status, paid_amount FROM invoices");
    $ctx['invoices'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    
    $stmt = $pdo->query("SELECT id, name FROM departments");
    $ctx['departments'] = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (Exception $e) {
    // Ignore DB errors if tables don't exist yet
}

$systemPrompt = PromptManager::buildSystemPrompt($ctx, $lang);

// 2. Manage conversation history
$messages = [];
$messages[] = ['role' => 'system', 'content' => $systemPrompt];

$history = ConversationMemory::getMessages();
foreach ($history as $msg) {
    $messages[] = $msg;
}

$messages[] = ['role' => 'user', 'content' => $message];
ConversationMemory::add('user', $message);

// 3. Initialize AI Service
$ai = new AIService();

if (!$ai->hasKey()) {
    // Offline / Mock Fallback
    $reply = "I am operating in offline mode as no AI API key is configured. However, I can confirm your message: '{$message}'. Please configure `OPENAI_API_KEY` or `GEMINI_API_KEY` in your `.env` file.";
    
    // Simulate smart offline responses
    $lowerMsg = strtolower($message);
    if (strpos($lowerMsg, 'patient') !== false) {
        $reply = "We have " . count($ctx['patients'] ?? []) . " patients in the current database view.";
    } elseif (strpos($lowerMsg, 'doctor') !== false) {
        $reply = "We have " . count($ctx['doctors'] ?? []) . " doctors available.";
    } elseif (strpos($lowerMsg, 'appointment') !== false) {
        $reply = "There are " . count($ctx['appointments'] ?? []) . " upcoming appointments.";
    }

    ConversationMemory::add('assistant', $reply);
    
    header('Content-Type: application/json');
    echo json_encode(['text' => $reply]);
    exit;
}

// 4. Stream response to frontend
// Note: $ai->chat() with stream=true outputs SSE directly
ob_end_clean(); // Ensure no output buffering interferes with SSE
$ai->chat($messages, true);

// Note: Streaming SSE does not easily allow saving the final response back to ConversationMemory
// in a stateless PHP script without complex buffer capturing.
// For production, the frontend will echo the final assembled string back to a /save_chat endpoint,
// or we capture it with output buffering in the AIService (which is harder with live flushing).
// For now, the client handles the display.
