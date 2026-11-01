<?php
declare(strict_types=1);

namespace App\DAO;

use PDO;

final class BD
{
    private static ?PDO $pdo = null;

    public static function conectar(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST'),
                getenv('DB_NAME')
            );

            self::$pdo = new PDO($dsn, (string) getenv('DB_USER'), (string) getenv('DB_PASS'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$pdo;
    }
}