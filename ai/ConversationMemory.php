<?php
/**
 * ConversationMemory.php — Session-based Chat History
 * Stores, retrieves, and clears conversation context per session.
 */

namespace Nucleus\AI;

class ConversationMemory
{
    private const SESSION_KEY = 'nucleus_ai_memory';
    private const MAX_TURNS   = 20; // Keep last 20 messages (10 turns)

    /** Add a message to conversation history. */
    public static function add(string $role, string $content): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $history   = $_SESSION[self::SESSION_KEY] ?? [];
        $history[] = ['role' => $role, 'content' => $content, 'ts' => time()];

        // Trim to max turns (keep system prompt at index 0 if present)
        if (count($history) > self::MAX_TURNS) {
            $history = array_slice($history, -self::MAX_TURNS);
        }

        $_SESSION[self::SESSION_KEY] = $history;
    }

    /** Get all messages formatted for AI API calls. */
    public static function getMessages(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $history = $_SESSION[self::SESSION_KEY] ?? [];

        // Return only role + content (strip timestamps for API)
        return array_map(fn($m) => [
            'role'    => $m['role'],
            'content' => $m['content'],
        ], $history);
    }

    /** Get history with timestamps (for display). */
    public static function getHistory(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    /** Clear all conversation history. */
    public static function clear(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[self::SESSION_KEY] = [];
    }

    /** Get number of stored messages. */
    public static function count(): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return count($_SESSION[self::SESSION_KEY] ?? []);
    }

    /** Store patient/doctor/appointment context for current session. */
    public static function setContext(string $type, mixed $data): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['nucleus_ai_ctx_' . $type] = $data;
    }

    /** Retrieve context by type. */
    public static function getContext(string $type): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['nucleus_ai_ctx_' . $type] ?? null;
    }
}
