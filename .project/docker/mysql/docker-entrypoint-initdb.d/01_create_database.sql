CREATE DATABASE IF NOT EXISTS `local` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
CREATE DATABASE IF NOT EXISTS `foreign` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE DATABASE IF NOT EXISTS `local_testing` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
CREATE DATABASE IF NOT EXISTS `foreign_testing` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

GRANT ALL ON *.* TO 'app'@'%';

FLUSH PRIVILEGES;