<?php
declare(strict_types=1);

/**
 * Soda Hotel - PostgreSQL / Neon database bootstrap.
 *
 * Database:
 *   - Neon PostgreSQL via DATABASE_URL
 *   - Optional PG_* variables for local PostgreSQL
 *
 * Runtime:
 *   - XAMPP Apache + PHP
 *   - PDO PostgreSQL
 */


/* =========================================================
 * SESSION
 * ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);

    session_start();
}


/* =========================================================
 * ENVIRONMENT
 * ========================================================= */

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    return $value === false ? $default : $value;
}


/**
 * Load .env file.
 */
function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file(
        $path,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
    );

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line === '') {
            continue;
        }

        if (str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        /*
         * Remove surrounding quotes.
         */
        if (
            strlen($value) >= 2 &&
            (
                ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                ($value[0] === "'" && $value[strlen($value) - 1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        /*
         * Do not overwrite an existing environment variable.
         */
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
        }
    }
}


/*
 * Load:
 *
 * C:\xampp\htdocs\hotel\.env
 */
loadEnvFile(__DIR__ . DIRECTORY_SEPARATOR . '.env');

/* =========================================================
 * DATABASE
 * ========================================================= */

/**
 * Build PDO connection information.
 *
 * Priority:
 *
 * 1. DATABASE_URL
 * 2. PG_* variables
 *
 * Neon:
 * Older libpq versions may not support Neon SNI.
 * Therefore the Neon endpoint ID is passed through:
 *
 * options=endpoint%3D<endpoint-id>
 */
function isNeonHost(string $host): bool
{
    $host = strtolower(trim($host));

    return $host !== '' && str_ends_with($host, '.neon.tech');
}


/**
 * Extract the Neon compute endpoint id from the hostname.
 *
 * Examples:
 *   ep-cool-darkness-123456.ap-southeast-1.aws.neon.tech
 *   ep-cool-darkness-123456-pooler.ap-southeast-1.aws.neon.tech
 *
 * Both become:
 *   ep-cool-darkness-123456
 */
function neonEndpointIdFromHost(string $host): ?string
{
    if (!isNeonHost($host)) {
        return null;
    }

    $firstLabel = explode('.', strtolower($host), 2)[0] ?? '';
    $endpointId = preg_replace('/-pooler$/', '', $firstLabel);

    if (
        $endpointId === null ||
        $endpointId === '' ||
        !str_starts_with($endpointId, 'ep-')
    ) {
        return null;
    }

    return $endpointId;
}


/**
 * Neon supports an old-libpq SNI fallback in the password field:
 *
 *   endpoint=<endpoint-id>;<password>
 *
 * If a DATABASE_URL already contains this legacy prefix, strip it here so
 * the first connection attempt can still use normal SNI/SCRAM on modern
 * clients. The prefix is added back only when Neon reports the SNI error.
 */
function stripNeonEndpointPasswordPrefix(string $password): string
{
    return preg_replace(
        '/^endpoint=ep-[A-Za-z0-9-]+[;$]/',
        '',
        $password,
        1
    ) ?? $password;
}


function withNeonEndpointPassword(
    string $endpointId,
    string $password
): string {
    return 'endpoint=' . $endpointId . ';' . $password;
}


/**
 * Build PDO connection information.
 *
 * Returns:
 *   [dsn, username, password, neonEndpointId|null]
 *
 * Priority:
 *   1. DATABASE_URL
 *   2. PG_* variables
 */
function buildPdoDsn(): array
{
    $databaseUrl = env('DATABASE_URL');

    /* =====================================================
     * DATABASE_URL (Neon / hosted PostgreSQL)
     * ===================================================== */

    if ($databaseUrl !== null && trim($databaseUrl) !== '') {
        $databaseUrl = trim($databaseUrl);

        if (
            !str_starts_with($databaseUrl, 'postgresql://') &&
            !str_starts_with($databaseUrl, 'postgres://')
        ) {
            throw new RuntimeException(
                'DATABASE_URL must start with postgresql:// or postgres://'
            );
        }

        $parts = parse_url($databaseUrl);

        if ($parts === false) {
            throw new RuntimeException('Invalid DATABASE_URL format.');
        }

        if (empty($parts['host'])) {
            throw new RuntimeException('DATABASE_URL is missing the database host.');
        }

        if (empty($parts['path'])) {
            throw new RuntimeException('DATABASE_URL is missing the database name.');
        }

        $host = (string)$parts['host'];
        $port = isset($parts['port']) ? (string)$parts['port'] : '5432';
        $db = rawurldecode(ltrim((string)$parts['path'], '/'));
        $user = isset($parts['user']) ? rawurldecode((string)$parts['user']) : '';
        $pass = isset($parts['pass']) ? rawurldecode((string)$parts['pass']) : '';

        if ($user === '') {
            throw new RuntimeException('DATABASE_URL is missing the database username.');
        }

        $endpointId = neonEndpointIdFromHost($host);

        // Normalize old password-field workarounds so we can try modern SNI first.
        if ($endpointId !== null) {
            $pass = stripNeonEndpointPasswordPrefix($pass);
        }

        // The uploaded project still had this placeholder. Fail with a useful
        // message instead of a confusing PostgreSQL authentication error.
        if (
            $pass === '' ||
            str_contains(strtoupper($pass), 'YOUR_PASSWORD') ||
            str_contains(strtoupper($pass), 'YOUR_NEON_PASSWORD') ||
            str_contains(strtoupper($pass), 'CHANGE_ME')
        ) {
            throw new RuntimeException(
                'DATABASE_URL does not contain a real database password. ' .
                'Replace YOUR_PASSWORD/CHANGE_ME in .env with the password from Neon.'
            );
        }

        $query = [];
        if (!empty($parts['query'])) {
            parse_str((string)$parts['query'], $query);
        }

        $sslmode = isset($query['sslmode']) && is_string($query['sslmode'])
            ? strtolower($query['sslmode'])
            : 'require';

        $allowedSslModes = [
            'disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'
        ];

        if (!in_array($sslmode, $allowedSslModes, true)) {
            $sslmode = 'require';
        }

        $dsn =
            'pgsql:' .
            'host=' . $host . ';' .
            'port=' . $port . ';' .
            'dbname=' . $db . ';' .
            'sslmode=' . $sslmode;

        return [
            $dsn,
            $user,
            $pass,
            $endpointId,
        ];
    }

    /* =====================================================
     * LOCAL POSTGRESQL FALLBACK
     * ===================================================== */

    $host = env('PGHOST', env('DB_HOST', 'localhost'));
    $port = env('PGPORT', env('DB_PORT', '5432'));
    $db = env('PGDATABASE', env('DB_NAME', 'hotel'));
    $user = env('PGUSER', env('DB_USER', 'postgres'));
    $pass = env('PGPASSWORD', env('DB_PASS', ''));
    $sslmode = strtolower((string)env('PGSSLMODE', 'prefer'));

    $allowedSslModes = [
        'disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'
    ];

    if (!in_array($sslmode, $allowedSslModes, true)) {
        $sslmode = 'prefer';
    }

    $dsn =
        'pgsql:' .
        'host=' . $host . ';' .
        'port=' . $port . ';' .
        'dbname=' . $db . ';' .
        'sslmode=' . $sslmode;

    return [
        $dsn,
        (string)$user,
        (string)$pass,
        null,
    ];
}


/* =========================================================
 * DATABASE RESULT
 * ========================================================= */

final class DbResult
{
    private array $rows;

    private int $index = 0;


    public function __construct(PDOStatement $statement)
    {
        $this->rows = $statement->fetchAll(
            PDO::FETCH_BOTH
        );
    }


    public function fetch_assoc(): array|false
    {
        if (!isset($this->rows[$this->index])) {
            return false;
        }

        $row = $this->rows[$this->index++];

        $assoc = [];

        foreach ($row as $key => $value) {

            if (is_string($key)) {
                $assoc[$key] = $value;
            }
        }

        return $assoc;
    }


    public function fetch_array(): array|false
    {
        if (!isset($this->rows[$this->index])) {
            return false;
        }

        return $this->rows[$this->index++];
    }


    public function num_rows(): int
    {
        return count($this->rows);
    }
}


/* =========================================================
 * DATABASE STATEMENT
 * ========================================================= */

final class DbStatement
{
    private PDOStatement $statement;

    private array $bound = [];


    public function __construct(
        PDO $pdo,
        string $sql
    ) {
        $this->statement = $pdo->prepare($sql);

        if ($this->statement === false) {
            throw new RuntimeException(
                'Failed to prepare SQL statement.'
            );
        }
    }


    /**
     * mysqli-style compatibility:
     *
     * bind_param("si", $name, $id)
     */
    public function bind_param(
        string $types,
        &...$vars
    ): void {

        foreach ($vars as $i => &$value) {

            $type = $types[$i] ?? 's';

            $pdoType = match ($type) {

                'i' => PDO::PARAM_INT,

                'b' => PDO::PARAM_BOOL,

                default => PDO::PARAM_STR,
            };


            $this->statement->bindValue(
                $i + 1,
                $value,
                $pdoType
            );


            $this->bound[$i + 1] = $value;
        }
    }


    public function execute(
        ?array $params = null
    ): bool {

        if ($params === null) {
            return $this->statement->execute();
        }

        return $this->statement->execute($params);
    }


    public function get_result(): DbResult
    {
        return new DbResult(
            $this->statement
        );
    }
}


/* =========================================================
 * DATABASE CONNECTION
 * ========================================================= */

final class DbConnection
{
    private PDO $pdo;

    private ?string $lastError = null;


    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    public function prepare(
        string $sql
    ): DbStatement {

        return new DbStatement(
            $this->pdo,
            $sql
        );
    }


    public function query(
        string $sql
    ): DbResult|bool {

        try {

            $stmt = $this->pdo->query($sql);

            if ($stmt === false) {
                return false;
            }

            return new DbResult($stmt);

        } catch (Throwable $e) {

            $this->lastError =
                $e->getMessage();

            return false;
        }
    }


    public function begin_transaction(): bool
    {
        return $this->pdo->beginTransaction();
    }


    public function commit(): bool
    {
        return $this->pdo->commit();
    }


    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }


    public function quote(
        string $value
    ): string {

        return $this->pdo->quote($value);
    }


    public function error(): string
    {
        return $this->lastError ?? '';
    }


    public function pdo(): PDO
    {
        return $this->pdo;
    }
}


/* =========================================================
 * CONNECT TO DATABASE
 * ========================================================= */

try {

    /*
     * Make sure PDO PostgreSQL exists.
     */
    if (!extension_loaded('pdo_pgsql')) {

        throw new RuntimeException(
            'pdo_pgsql extension is not enabled in XAMPP PHP.'
        );
    }


    /*
     * Build DSN.
     */
    [$dsn, $user, $pass, $neonEndpointId] =
        buildPdoDsn();


    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        /*
         * Keep compatibility with existing application SQL.
         */
        PDO::ATTR_EMULATE_PREPARES => true,
    ];


    /*
     * First try the normal connection. On modern libpq (>= 14), SNI is
     * supported and Neon can route the connection from the hostname.
     */
    try {
        $pdo = new PDO(
            $dsn,
            $user,
            $pass,
            $pdoOptions
        );
    } catch (PDOException $firstError) {
        $message = $firstError->getMessage();

        $isNeonSniError =
            $neonEndpointId !== null &&
            stripos($message, 'endpoint id is not specified') !== false;

        if (!$isNeonSniError) {
            throw $firstError;
        }

        /*
         * XAMPP installations can ship an old libpq without SNI support.
         * PDO_PGSQL does not reliably expose arbitrary URI query options,
         * so use Neon's documented password-field fallback only after the
         * normal connection fails specifically with the SNI error.
         */
        $fallbackPassword = withNeonEndpointPassword(
            $neonEndpointId,
            $pass
        );

        try {
            $pdo = new PDO(
                $dsn,
                $user,
                $fallbackPassword,
                $pdoOptions
            );
        } catch (PDOException $fallbackError) {
            throw new RuntimeException(
                'Neon SNI fallback was applied, but the database connection still failed: ' .
                $fallbackError->getMessage(),
                0,
                $fallbackError
            );
        }
    }


    /*
     * Database connection successful.
     */
    $conn = new DbConnection($pdo);

} catch (Throwable $e) {

    http_response_code(500);


    /*
     * Show REAL database error while debugging.
     *
     * This is intentionally enabled while
     * troubleshooting the Neon connection.
     */
    exit(
        '<!DOCTYPE html>' .
        '<html>' .
        '<head>' .
        '<meta charset="UTF-8">' .
        '<title>Database Connection Error</title>' .
        '</head>' .
        '<body style="font-family:Arial,sans-serif;padding:30px">' .

        '<h2>Database connection failed</h2>' .

        '<p><strong>Actual error:</strong></p>' .

        '<pre style="' .
        'background:#f5f5f5;' .
        'padding:15px;' .
        'border-radius:8px;' .
        'white-space:pre-wrap;' .
        '">' .

        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        ) .

        '</pre>' .

        '</body>' .
        '</html>'
    );
}


/* =========================================================
 * LEGACY DATABASE HELPERS
 * ========================================================= */

function db_query(
    DbConnection $conn,
    string $sql
): DbResult|bool {

    return $conn->query($sql);
}


function db_fetch_assoc(
    DbResult $result
): array|false {

    return $result->fetch_assoc();
}


function db_fetch_array(
    DbResult $result
): array|false {

    return $result->fetch_array();
}


function db_num_rows(
    DbResult $result
): int {

    return $result->num_rows();
}


function db_error(
    DbConnection $conn
): string {

    return $conn->error();
}


function db_escape(
    DbConnection $conn,
    string $value
): string {

    /*
     * Compatibility with old code.
     *
     * New code should use prepared statements.
     */
    return str_replace(
        "'",
        "''",
        $value
    );
}


/* =========================================================
 * SESSION CART
 * ========================================================= */

$_SESSION['cart'] ??= [];


/* =========================================================
 * OUTPUT ESCAPING
 * ========================================================= */

function e(
    ?string $value
): string {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


/* =========================================================
 * AUTHENTICATION
 * ========================================================= */

function requireLogin(): void
{
    if (empty($_SESSION['Username'])) {

        header(
            'Location: loginhotel.php'
        );

        exit;
    }
}


function requireAdmin(): void
{
    if (
        empty($_SESSION['Username']) ||
        ($_SESSION['status'] ?? '') !== 'admin'
    ) {

        header(
            'Location: loginhotel.php'
        );

        exit;
    }
}


/* =========================================================
 * CSRF
 * ========================================================= */

function csrfToken(): string
{
    if (!isset($_SESSION['csrf'])) {

        $_SESSION['csrf'] =
            bin2hex(
                random_bytes(32)
            );
    }

    return $_SESSION['csrf'];
}


function verifyCsrf(): void
{
    $sessionToken =
        $_SESSION['csrf'] ?? '';

    $postedToken =
        $_POST['csrf'] ?? '';


    if (
        !is_string($postedToken) ||
        !is_string($sessionToken) ||
        !hash_equals(
            $sessionToken,
            $postedToken
        )
    ) {

        http_response_code(419);

        exit(
            'Invalid request token.'
        );
    }
}