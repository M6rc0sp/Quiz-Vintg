-- ============================================================
-- Schema SQLite — Quiz Vintg
-- ============================================================
-- O banco (quiz_vintg.sqlite) é criado automaticamente
-- pelo config.php. Este arquivo é apenas documentação.
-- ============================================================

CREATE TABLE IF NOT EXISTS leads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    profile_key TEXT NOT NULL CHECK(profile_key IN ('A','B','C','D')),
    profile_name TEXT NOT NULL,
    answers TEXT NOT NULL DEFAULT '[]',
    user_agent TEXT DEFAULT NULL,
    ip_address TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_leads_email ON leads(email);
CREATE INDEX IF NOT EXISTS idx_leads_created_at ON leads(created_at);
CREATE INDEX IF NOT EXISTS idx_leads_profile ON leads(profile_key);
