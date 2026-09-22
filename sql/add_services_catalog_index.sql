-- Supports the active customer service catalog used by Php/serv.php.
USE cataleya_db;

CREATE INDEX idx_services_catalog
    ON services (is_active, main_category, sub_category, name);
