Importing the `cataleya_db` SQL dump

phpMyAdmin (GUI):
1. Open http://localhost/phpmyadmin
2. Create a new database named `cataleya_db` if it doesn't exist.
3. Select the database, go to the "Import" tab.
4. Choose the file `cataleya_db_dump.sql` from the `sql/` folder and click "Go".

MySQL CLI (Windows with XAMPP):
- Open a Command Prompt or PowerShell and run:

```
cd "C:\xampp\mysql\bin"
mysql -u root -p < "C:\xampp\htdocs\Beauty Essence\sql\cataleya_db_dump.sql"
```

(Enter your MySQL password when prompted; leave blank and press Enter if no password.)

Alternate CLI using full path to the dump from any folder:

```
mysql -u root -p cataleya_db < "C:\xampp\htdocs\Beauty Essence\sql\cataleya_db_dump.sql"
```

Notes:
- `config/database.php` in the project already uses `cataleya_db` and default `root`/empty password: [config/database.php](config/database.php#L1-L20).
- If your MySQL root user has a password, replace `-p` with `-pYourPassword` (no space) or enter it when prompted.
- If using phpMyAdmin, large dumps may time out; use CLI for reliability.
