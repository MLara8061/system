-- Crear bases de datos para los 3 sistemas
CREATE DATABASE IF NOT EXISTS u499728070_biomedicacun CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS u499728070_sistemascun CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS u499728070_manttocun CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuarios de base de datos
GRANT ALL PRIVILEGES ON u499728070_biomedicacun.* TO 'u499728070_biomedica'@'localhost' IDENTIFIED BY 'Q;|\p5&u%<r#3}7,';
GRANT ALL PRIVILEGES ON u499728070_sistemascun.* TO 'u499728070_sistemas'@'localhost' IDENTIFIED BY '=_ju7{21BnHa})hX';
GRANT ALL PRIVILEGES ON u499728070_manttocun.* TO 'u499728070_mantto'@'localhost' IDENTIFIED BY 'gIWXujkCO9/{%$37';

-- Flush privileges
FLUSH PRIVILEGES;

-- Verificar bases de datos creadas
SHOW DATABASES LIKE 'u499728070_%';

-- Verificar usuarios creados
SELECT User, Host FROM mysql.user WHERE User LIKE 'u499728070_%';
