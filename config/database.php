<?php
declare(strict_types=1);

final class DatabaseException extends RuntimeException
{
}

final class SqlServerDatabase
{
    private string $sqlcmdPath;
    private string $server;
    private string $database;

    public function __construct()
    {
        $this->sqlcmdPath = getenv('SQLCMD_PATH') ?: 'C:\\Program Files\\Microsoft SQL Server\\Client SDK\\ODBC\\180\\Tools\\Binn\\SQLCMD.EXE';
        $this->server = getenv('SQLSERVER_HOST') ?: 'localhost';
        $this->database = getenv('SQLSERVER_DATABASE') ?: 'ngekos_db';
    }

    /** @return list<array<string, mixed>> */
    public function getListings(): array
    {
        $sql = <<<'SQL'
SET NOCOUNT ON;
DECLARE @json NVARCHAR(MAX) = (
    SELECT
        id,
        name,
        location,
        city,
        price,
        period,
        type,
        rating,
        reviews,
        image_url AS image,
        tag,
        features_json AS features,
        description
    FROM dbo.listings
    WHERE is_active = 1
    ORDER BY id
    FOR JSON PATH
);

;WITH numbers AS (
    SELECT TOP (1000) ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS number
    FROM sys.all_objects AS a
    CROSS JOIN sys.all_objects AS b
)
SELECT SUBSTRING(@json, ((number - 1) * 200) + 1, 200)
FROM numbers
WHERE ((number - 1) * 200) < LEN(@json)
ORDER BY number;
SQL;

        $rows = $this->queryJson($sql);
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['price'] = (int) $row['price'];
            $row['rating'] = (float) $row['rating'];
            $row['reviews'] = (int) $row['reviews'];
            $row['features'] = json_decode((string) $row['features'], true) ?: [];
        }
        unset($row);

        return $rows;
    }

    public function saveInquiry(string $name, string $phone, string $listing): void
    {
        $sql = sprintf(
            "SET NOCOUNT ON; INSERT INTO dbo.inquiries (name, phone, listing_name) VALUES (%s, %s, %s);",
            $this->literal($name),
            $this->literal($phone),
            $this->literal($listing),
        );
        $this->execute($sql);
    }

    /** @return array<string, mixed>|null */
    public function findUserByEmail(string $email): ?array
    {
        $sql = sprintf(
            "SET NOCOUNT ON; SELECT id, name, email, password_hash, role FROM dbo.users WHERE email = %s FOR JSON PATH;",
            $this->literal(strtolower($email)),
        );
        $users = $this->queryJson($sql);
        if ($users === []) {
            return null;
        }

        $user = $users[0];
        $user['id'] = (int) $user['id'];
        return $user;
    }

    /** @return array<string, mixed> */
    public function createUser(string $name, string $email, string $passwordHash): array
    {
        if ($this->findUserByEmail($email) !== null) {
            throw new DatabaseException('Email tersebut sudah terdaftar.');
        }

        $sql = sprintf(
            "SET NOCOUNT ON; INSERT INTO dbo.users (name, email, password_hash) VALUES (%s, %s, %s); SELECT CAST(SCOPE_IDENTITY() AS INT) AS id, %s AS name, %s AS email, N'tenant' AS role FOR JSON PATH, WITHOUT_ARRAY_WRAPPER;",
            $this->literal($name),
            $this->literal(strtolower($email)),
            $this->literal($passwordHash),
            $this->literal($name),
            $this->literal(strtolower($email)),
        );
        $user = $this->queryJsonObject($sql);
        $user['id'] = (int) $user['id'];
        return $user;
    }

    /** @return list<array<string, mixed>> */
    private function queryJson(string $sql): array
    {
        $output = $this->execute($sql);
        $output = preg_replace('/^\xEF\xBB\xBF/', '', trim($output)) ?? '';
        $output = preg_replace('/\R/', '', $output) ?? '';
        if ($output === '') {
            return [];
        }

        $decoded = json_decode($output, true);
        if (!is_array($decoded)) {
            throw new DatabaseException('SQL Server mengembalikan JSON tidak valid: ' . substr($output, 0, 300));
        }

        return $decoded;
    }

    /** @return array<string, mixed> */
    private function queryJsonObject(string $sql): array
    {
        $output = $this->execute($sql);
        $output = preg_replace('/^\xEF\xBB\xBF/', '', trim($output)) ?? '';
        $output = preg_replace('/\R/', '', $output) ?? '';
        $decoded = json_decode($output, true);
        if (!is_array($decoded)) {
            throw new DatabaseException('SQL Server mengembalikan data user tidak valid.');
        }

        return $decoded;
    }

    private function execute(string $sql): string
    {
        $inputFile = tempnam(sys_get_temp_dir(), 'ngekos_sql_');
        if ($inputFile === false) {
            throw new DatabaseException('Tidak dapat membuat file SQL sementara.');
        }

        try {
            if (file_put_contents($inputFile, $sql) === false) {
                throw new DatabaseException('Tidak dapat menulis file SQL sementara.');
            }

            $command = sprintf(
                '"%s" -S "%s" -E -C -d "%s" -b -r 1 -h -1 -W -w 65535 -f 65001 -i "%s"',
                $this->sqlcmdPath,
                $this->server,
                $this->database,
                $inputFile,
            );
            $pipes = [];
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) {
                throw new DatabaseException('Tidak dapat menjalankan sqlcmd.');
            }

            $stdout = stream_get_contents($pipes[1]) ?: '';
            $stderr = stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if ($exitCode !== 0) {
                throw new DatabaseException(trim($stderr ?: $stdout) ?: 'Query SQL Server gagal.');
            }

            return $stdout;
        } finally {
            @unlink($inputFile);
        }
    }

    private function literal(string $value): string
    {
        return "N'" . str_replace("'", "''", $value) . "'";
    }
}
