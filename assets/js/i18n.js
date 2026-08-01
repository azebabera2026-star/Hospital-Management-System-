/**
 * Nucleus HMS — English-only search normalizer.
 * Amharic translation logic has been removed.
 * The system is permanently set to English (en-US).
 */

// Language is permanently English — no switching.
const currentLang = 'en';

/**
 * Case-insensitive search query normalizer.
 * Lowercases and trims the input for consistent matching
 * across patient names, doctor names, and hospital records.
 */
function normalizeSearchQuery(query) {
  if (!query) return '';
  return query.toLowerCase().trim();
}
