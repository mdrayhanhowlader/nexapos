<?php

class DB
{
    private static ?PDO $connection = null;

    public static function connect(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $host    = Config::db('host',    'localhost');
        $port    = Config::db('port',    '3306');
        $name    = Config::db('name',    'nexapos_db');
        $user    = Config::db('user',    'root');
        $pass    = Config::db('pass',    '');
        $charset = Config::db('charset', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ];

        try {
            self::$connection = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            Logger::critical('Database connection failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            if (is_ajax()) {
                Response::error('Database connection failed. Check logs.', 500);
            }
            die('Database connection failed. Check storage/logs for details.');
        }

        return self::$connection;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);
        try {
            $stmt = self::connect()->prepare($sql);
            $stmt->execute($params);
            $elapsed = microtime(true) - $start;
            if (Config::app('debug')) {
                Logger::query($sql, $params, $elapsed);
            }
            return $stmt;
        } catch (PDOException $e) {
            Logger::exception($e, "SQL: {$sql} | Params: " . json_encode($params));
            throw new RuntimeException(
                'Query failed on line ' . $e->getLine() . ': ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Insert data cannot be empty for table: {$table}");
        }
        $cols   = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $places = implode(', ', array_fill(0, count($data), '?'));
        self::query("INSERT INTO `{$table}` ({$cols}) VALUES ({$places})", array_values($data));
        return (int) self::connect()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException("Update data cannot be empty for table: {$table}");
        }
        $set  = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
        $stmt = self::query(
            "UPDATE `{$table}` SET {$set} WHERE {$where}",
            array_merge(array_values($data), $whereParams)
        );
        return $stmt->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::query("DELETE FROM `{$table}` WHERE {$where}", $params)->rowCount();
    }

    public static function exists(string $table, string $where, array $params = []): bool
    {
        $row = self::fetch("SELECT 1 FROM `{$table}` WHERE {$where} LIMIT 1", $params);
        return $row !== null;
    }

    public static function count(string $table, string $where = '1=1', array $params = []): int
    {
        $row = self::fetch("SELECT COUNT(*) as n FROM `{$table}` WHERE {$where}", $params);
        return (int) ($row['n'] ?? 0);
    }

    public static function beginTransaction(): void
    {
        self::connect()->beginTransaction();
    }

    public static function commit(): void
    {
        self::connect()->commit();
    }

    public static function rollback(): void
    {
        if (self::connect()->inTransaction()) {
            self::connect()->rollBack();
        }
    }

    public static function lastInsertId(): int
    {
        return (int) self::connect()->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (Throwable $e) {
            self::rollback();
            Logger::exception($e, 'Transaction rolled back');
            throw $e;
        }
    }
}
