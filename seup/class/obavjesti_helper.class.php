<?php

/**
 * Plaćena licenca
 * (c) 2025 8Core Association
 * Tomislav Galić <tomislav@8core.hr>
 * Marko Šimunović <marko@8core.hr>
 * Web: https://8core.hr
 * Kontakt: info@8core.hr | Tel: +385 099 851 0717
 */

class Obavjesti_helper
{
    /**
     * Create notification tables if they don't exist
     */
    public static function createNotificationTables($db)
    {
        $sql_statements = [
            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_obavjesti (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                naslov VARCHAR(255) NOT NULL,
                subjekt ENUM('info', 'upozorenje', 'nadogradnja', 'hitno', 'vazno') NOT NULL DEFAULT 'info',
                sadrzaj TEXT NOT NULL,
                vanjski_link VARCHAR(512) DEFAULT NULL,
                kreirao_user_id INT NOT NULL,
                datum_kreiranja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                aktivna TINYINT(1) NOT NULL DEFAULT 1,
                INDEX idx_aktivna (aktivna),
                INDEX idx_datum (datum_kreiranja),
                INDEX idx_kreirao (kreirao_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_procitane_obavjesti (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                obavjest_id INT NOT NULL,
                user_id INT NOT NULL,
                datum_procitano DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_obavjest (obavjest_id, user_id),
                INDEX idx_user (user_id),
                INDEX idx_obavjest (obavjest_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "a_obrisane_obavjesti (
                rowid INT AUTO_INCREMENT PRIMARY KEY,
                obavjest_id INT NOT NULL,
                user_id INT NOT NULL,
                datum_brisanja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                naslov VARCHAR(255) NOT NULL,
                sadrzaj TEXT NOT NULL,
                INDEX idx_user (user_id),
                INDEX idx_datum (datum_brisanja),
                INDEX idx_obavjest (obavjest_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        foreach ($sql_statements as $sql) {
            $result = $db->query($sql);
            if (!$result) {
                error_log("SEUP Obavjesti: Failed to create table - " . $db->lasterror());
                return false;
            }
        }

        return true;
    }

    /**
     * Create new notification
     */
    public static function createObavjest($db, $naslov, $subjekt, $sadrzaj, $vanjski_link, $user_id)
    {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "a_obavjesti
                (naslov, subjekt, sadrzaj, vanjski_link, kreirao_user_id, datum_kreiranja, aktivna)
                VALUES ('" . $db->escape($naslov) . "',
                        '" . $db->escape($subjekt) . "',
                        '" . $db->escape($sadrzaj) . "',
                        " . ($vanjski_link ? "'" . $db->escape($vanjski_link) . "'" : "NULL") . ",
                        " . (int)$user_id . ",
                        NOW(),
                        1)";

        $result = $db->query($sql);

        if ($result) {
            return $db->last_insert_id(MAIN_DB_PREFIX . "a_obavjesti");
        }

        return false;
    }

    /**
     * Get all active notifications
     */
    public static function getActiveObavjesti($db)
    {
        $sql = "SELECT o.*, u.lastname, u.firstname
                FROM " . MAIN_DB_PREFIX . "a_obavjesti o
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON o.kreirao_user_id = u.rowid
                WHERE o.aktivna = 1
                ORDER BY o.datum_kreiranja DESC";

        $result = $db->query($sql);
        $obavjesti = [];

        if ($result) {
            while ($obj = $db->fetch_object($result)) {
                $obavjesti[] = $obj;
            }
        }

        return $obavjesti;
    }

    /**
     * Get unread notifications for specific user
     */
    public static function getUnreadObavjestiForUser($db, $user_id)
    {
        $sql = "SELECT o.*, u.lastname, u.firstname
                FROM " . MAIN_DB_PREFIX . "a_obavjesti o
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON o.kreirao_user_id = u.rowid
                WHERE o.aktivna = 1
                AND o.rowid NOT IN (
                    SELECT obavjest_id FROM " . MAIN_DB_PREFIX . "a_procitane_obavjesti
                    WHERE user_id = " . (int)$user_id . "
                )
                AND o.rowid NOT IN (
                    SELECT obavjest_id FROM " . MAIN_DB_PREFIX . "a_obrisane_obavjesti
                    WHERE user_id = " . (int)$user_id . "
                )
                ORDER BY o.datum_kreiranja DESC";

        $result = $db->query($sql);
        $obavjesti = [];

        if ($result) {
            while ($obj = $db->fetch_object($result)) {
                $obavjesti[] = $obj;
            }
        }

        return $obavjesti;
    }

    /**
     * Get unread notification count for user
     */
    public static function getUnreadCountForUser($db, $user_id)
    {
        $sql = "SELECT COUNT(*) as count
                FROM " . MAIN_DB_PREFIX . "a_obavjesti o
                WHERE o.aktivna = 1
                AND o.rowid NOT IN (
                    SELECT obavjest_id FROM " . MAIN_DB_PREFIX . "a_procitane_obavjesti
                    WHERE user_id = " . (int)$user_id . "
                )
                AND o.rowid NOT IN (
                    SELECT obavjest_id FROM " . MAIN_DB_PREFIX . "a_obrisane_obavjesti
                    WHERE user_id = " . (int)$user_id . "
                )";

        $result = $db->query($sql);

        if ($result && $obj = $db->fetch_object($result)) {
            return (int)$obj->count;
        }

        return 0;
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead($db, $obavjest_id, $user_id)
    {
        $sql = "INSERT IGNORE INTO " . MAIN_DB_PREFIX . "a_procitane_obavjesti
                (obavjest_id, user_id, datum_procitano)
                VALUES (" . (int)$obavjest_id . ", " . (int)$user_id . ", NOW())";

        return $db->query($sql);
    }

    /**
     * Mark all notifications as read for user
     */
    public static function markAllAsRead($db, $user_id)
    {
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "a_obavjesti
                WHERE aktivna = 1
                AND rowid NOT IN (
                    SELECT obavjest_id FROM " . MAIN_DB_PREFIX . "a_procitane_obavjesti
                    WHERE user_id = " . (int)$user_id . "
                )";

        $result = $db->query($sql);
        $success = true;

        if ($result) {
            while ($obj = $db->fetch_object($result)) {
                if (!self::markAsRead($db, $obj->rowid, $user_id)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Delete notification for user (move to deleted table)
     */
    public static function deleteForUser($db, $obavjest_id, $user_id)
    {
        // Get notification details first
        $sql = "SELECT naslov, sadrzaj FROM " . MAIN_DB_PREFIX . "a_obavjesti
                WHERE rowid = " . (int)$obavjest_id;

        $result = $db->query($sql);

        if ($result && $obj = $db->fetch_object($result)) {
            // Insert into deleted table
            $sql_insert = "INSERT INTO " . MAIN_DB_PREFIX . "a_obrisane_obavjesti
                          (obavjest_id, user_id, datum_brisanja, naslov, sadrzaj)
                          VALUES (" . (int)$obavjest_id . ",
                                  " . (int)$user_id . ",
                                  NOW(),
                                  '" . $db->escape($obj->naslov) . "',
                                  '" . $db->escape($obj->sadrzaj) . "')";

            return $db->query($sql_insert);
        }

        return false;
    }

    /**
     * Delete all notifications for user
     */
    public static function deleteAllForUser($db, $user_id)
    {
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "a_obavjesti
                WHERE aktivna = 1
                AND rowid NOT IN (
                    SELECT obavjest_id FROM " . MAIN_DB_PREFIX . "a_obrisane_obavjesti
                    WHERE user_id = " . (int)$user_id . "
                )";

        $result = $db->query($sql);
        $success = true;

        if ($result) {
            while ($obj = $db->fetch_object($result)) {
                if (!self::deleteForUser($db, $obj->rowid, $user_id)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Deactivate notification (admin only)
     */
    public static function deactivateObavjest($db, $obavjest_id)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . "a_obavjesti
                SET aktivna = 0
                WHERE rowid = " . (int)$obavjest_id;

        return $db->query($sql);
    }

    /**
     * Get notification by ID
     */
    public static function getObavjestById($db, $obavjest_id)
    {
        $sql = "SELECT o.*, u.lastname, u.firstname
                FROM " . MAIN_DB_PREFIX . "a_obavjesti o
                LEFT JOIN " . MAIN_DB_PREFIX . "user u ON o.kreirao_user_id = u.rowid
                WHERE o.rowid = " . (int)$obavjest_id;

        $result = $db->query($sql);

        if ($result && $obj = $db->fetch_object($result)) {
            return $obj;
        }

        return null;
    }
}
