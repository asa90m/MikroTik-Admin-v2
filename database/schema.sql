-- ============================================================================
-- قاعدة بيانات تطبيق إدارة الهوتسبوت / يوزر مانجر عبر ميكروتيك
-- ============================================================================
CREATE DATABASE IF NOT EXISTS connect_visual
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE connect_visual;

-- ----------------------------------------------------------------------------
-- 1) إعدادات الربط بالراوتر (كما هي في التحليل الأصلي)
-- ----------------------------------------------------------------------------
CREATE TABLE routers_config (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    router_name VARCHAR(100) NOT NULL,
    api_host    VARCHAR(100) NOT NULL,
    api_port    INT NOT NULL DEFAULT 8728,
    api_user    VARCHAR(100) NOT NULL,
    api_pass    VARCHAR(255) NOT NULL,        -- يُفضّل تشفيرها بـ openssl_encrypt قبل التخزين
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- 2) قوالب وفئات الكروت (كما هي في التحليل الأصلي)
-- ----------------------------------------------------------------------------
CREATE TABLE card_profiles (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    profile_name VARCHAR(100) NOT NULL,        -- يطابق اسم profile في الميكروتيك
    price        DECIMAL(10,2) NOT NULL DEFAULT 0,
    validity     VARCHAR(50),
    network_name VARCHAR(100),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- 3) أرشيف الكروت — يشمل الكروت العادية وكروت الشحن الفوري معاً
--    (حقل card_type يميّز بينها بدل إنشاء جدول مستقل لكروت الشحن)
-- ----------------------------------------------------------------------------
CREATE TABLE cards_archive (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50) NOT NULL,
    password   VARCHAR(50) NOT NULL,
    profile_id INT NULL,
    card_type  ENUM('hotspot', 'usermanager', 'topup') NOT NULL DEFAULT 'hotspot',
    price      DECIMAL(10,2) DEFAULT 0,
    is_printed TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    printed_at TIMESTAMP NULL,
    CONSTRAINT fk_cards_profile FOREIGN KEY (profile_id)
        REFERENCES card_profiles(id) ON DELETE SET NULL,
    INDEX idx_printed (is_printed),
    INDEX idx_type (card_type)
);

-- ----------------------------------------------------------------------------
-- 4) إضافة: إعدادات قالب الطباعة (مذكورة في وصف الشاشات، بدون جدول مخصص
--    في التحليل الأصلي — أضفناها هنا لأن الواجهة تتيح تخصيصها)
-- ----------------------------------------------------------------------------
CREATE TABLE print_templates (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    network_name   VARCHAR(100) NOT NULL DEFAULT 'شبكتي',
    logo_path       VARCHAR(255),
    support_phone   VARCHAR(30),
    paper_width_mm  INT NOT NULL DEFAULT 58,   -- 58 أو 80
    template_html   TEXT,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- 5) إضافة: حماية دخول لوحة التحكم نفسها (لم يذكرها التحليل الأصلي صراحة،
--    لكنها ضرورية لأن اللوحة تتحكم بالراوتر وبأرشيف الكروت)
-- ----------------------------------------------------------------------------
CREATE TABLE admin_users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,        -- استخدم password_hash() في PHP
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- بيانات ابتدائية تجريبية
-- ----------------------------------------------------------------------------
INSERT INTO card_profiles (profile_name, price, validity, network_name) VALUES
('3 ساعات',  5.00,  '3 ساعات', 'شبكتي'),
('يوم كامل', 10.00, '1 يوم',   'شبكتي'),
('أسبوع',    50.00, '7 أيام',  'شبكتي'),
('شهر',      150.00,'30 يوم',  'شبكتي');

INSERT INTO print_templates (network_name, support_phone, paper_width_mm) VALUES
('شبكتي', '0500000000', 58);

-- مثال: عدّل القيم التالية بمعلومات راوترك الحقيقي قبل تشغيل التطبيق
INSERT INTO routers_config (router_name, api_host, api_port, api_user, api_pass) VALUES
('MK-HOTSPOT-01', '192.168.88.1', 8728, 'admin', 'CHANGE_ME');
