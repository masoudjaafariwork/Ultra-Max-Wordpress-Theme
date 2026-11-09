-- Separate database for the PHPUnit suite, so tests never touch the dev site.
CREATE DATABASE IF NOT EXISTS wordpress_test;
