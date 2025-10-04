-- Migration Script: v1.0.2 to v1.0.3
-- HCIS.YSQ Plugin
-- Date: 2025-10-01

-- WARNING: Backup database sebelum menjalankan script ini!

-- 1. Rename table hrissq_employees to hrissq_users (jika ada)
-- Note: Ganti 'wp_' dengan table prefix Anda

-- Cek apakah table lama ada
SELECT COUNT(*) FROM information_schema.tables
WHERE table_schema = DATABASE()
AND table_name = 'wp_hrissq_employees';

-- Jika ada, rename table
RENAME TABLE wp_hrissq_employees TO wp_hrissq_users;

-- 2. Alter table users - tambah kolom password jika belum ada
ALTER TABLE wp_hrissq_users
ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT ''
AFTER no_hp;

-- 3. Alter table users - rename kolom hp ke no_hp jika perlu
-- (Skip jika sudah benar)
-- ALTER TABLE wp_hrissq_users CHANGE COLUMN hp no_hp VARCHAR(32) DEFAULT '';

-- 4. Update foreign key di trainings table
-- Drop foreign key lama
ALTER TABLE wp_hrissq_trainings
DROP FOREIGN KEY IF EXISTS fk_emp;

-- Rename kolom employee_id ke user_id
ALTER TABLE wp_hrissq_trainings
CHANGE COLUMN employee_id user_id BIGINT UNSIGNED NOT NULL;

-- Tambah foreign key baru
ALTER TABLE wp_hrissq_trainings
ADD CONSTRAINT fk_user
FOREIGN KEY (user_id) REFERENCES wp_hrissq_users(id) ON DELETE CASCADE;

-- 5. Verifikasi struktur tabel
DESCRIBE wp_hrissq_users;
DESCRIBE wp_hrissq_trainings;
DESCRIBE wp_hrissq_profiles;

-- 6. Cek data
SELECT COUNT(*) as total_users FROM wp_hrissq_users;
SELECT COUNT(*) as total_trainings FROM wp_hrissq_trainings;
SELECT COUNT(*) as total_profiles FROM wp_hrissq_profiles;

-- Done!
-- Plugin siap digunakan dengan versi 1.0.3
