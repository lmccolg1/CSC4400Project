<?php
/**
 * auth.php — shared session security helper
 * Include this at the TOP of every protected page (after session_start).
 *
 * Provides:
 *  - Session fixation protection (regenerate ID on login)
 *  - Idle timeout (30 minutes)
 *  - Guest vs. user access control
 *  - Single require_login() helper
 */

define('SESSION_TIMEOUT', 1800); // 30 minutes

/**
 * Call once per page on every protected page.
 * $allow_guest = true  → guest sessions may proceed
 * $allow_guest = false → only real (non-guest) logged-in users
 */
function require_login(bool $allow_guest = false): void
{
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: index.php');
        exit();
    }

    // Idle timeout check
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            header('Location: index.php?timeout=1');
            exit();
        }
    }
    $_SESSION['last_activity'] = time();

    // Block guests from pages that need a real account
    if (!$allow_guest && ($_SESSION['user_type'] ?? '') === 'guest') {
        header('Location: index.php?guest_blocked=1');
        exit();
    }
}

/**
 * Call this immediately after a successful credential check, before
 * writing any session values, to prevent session fixation.
 */
function regenerate_session(): void
{
    session_regenerate_id(true);
}
