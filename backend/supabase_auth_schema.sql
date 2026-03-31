-- Run this script in Supabase SQL Editor.

CREATE TABLE IF NOT EXISTS `public`.users (
    id BIGSERIAL PRIMARY KEY,
    full_name TEXT NOT NULL,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    major TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_users_username ON `public`.users (username);
CREATE INDEX IF NOT EXISTS idx_users_email ON `public`.users (email);
