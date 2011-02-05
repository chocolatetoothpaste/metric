CREATE DATABASE IF NOT EXISTS test_admin;
USE test_admin;

GRANT ALL PRIVILEGES ON test_admin.* TO test_user@localhost IDENTIFIED BY 'test_pass';
