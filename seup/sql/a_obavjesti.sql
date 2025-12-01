-- ============================================================================
-- SQL migration for SEUP Notification System
-- Author: Tomislav Galić <tomislav@8core.hr>
-- Copyright: 2025 8Core Association
-- ============================================================================

-- Tablica: a_obavjesti
-- Glavna tablica za sve obavjesti koje šalje administrator
CREATE TABLE IF NOT EXISTS llx_a_obavjesti (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablica: a_procitane_obavjesti
-- Evidentira koje je obavjesti korisnik pročitao
CREATE TABLE IF NOT EXISTS llx_a_procitane_obavjesti (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    obavjest_id INT NOT NULL,
    user_id INT NOT NULL,
    datum_procitano DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_obavjest (obavjest_id, user_id),
    INDEX idx_user (user_id),
    INDEX idx_obavjest (obavjest_id),
    FOREIGN KEY (obavjest_id) REFERENCES llx_a_obavjesti(rowid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablica: a_obrisane_obavjesti
-- Evidentira obrisane obavjesti po korisnicima (za audit trail)
CREATE TABLE IF NOT EXISTS llx_a_obrisane_obavjesti (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    obavjest_id INT NOT NULL,
    user_id INT NOT NULL,
    datum_brisanja DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    naslov VARCHAR(255) NOT NULL,
    sadrzaj TEXT NOT NULL,
    INDEX idx_user (user_id),
    INDEX idx_datum (datum_brisanja),
    INDEX idx_obavjest (obavjest_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
