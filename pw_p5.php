<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

$charset = 'utf8mb4';
$dsn = "mysql:host=$hostname;dbname=$database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

function tableExists(PDO $pdo, string $tableName, string $databaseName): bool
{
    $sql = "SELECT COUNT(*) AS n
            FROM information_schema.tables
            WHERE table_schema = :dbname
              AND table_name = :tname";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':dbname' => $databaseName,
        ':tname'  => $tableName
    ]);
    $row = $stmt->fetch();
    return ($row && (int)$row['n'] > 0);
}

$user_session_key = $_SESSION['user_session_key'] ?? '';
$deleted_runs = 0;

try {
    $pdo = new PDO($dsn, $username, $password, $options);

    if ($user_session_key !== '') {
        $pdo->beginTransaction();

        $run_sql = "SELECT run_id
                    FROM runs
                    WHERE user_session_key = :usk
                      AND run_type <> 'example'";

        $run_stmt = $pdo->prepare($run_sql);
        $run_stmt->execute([':usk' => $user_session_key]);
        $run_ids = $run_stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($run_ids)) {
            $placeholders = implode(',', array_fill(0, count($run_ids), '?'));

            if (tableExists($pdo, 'motif_hits', $database)) {
                $sql = "DELETE FROM motif_hits WHERE run_id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($run_ids);
            }

            if (tableExists($pdo, 'run_files', $database)) {
                $sql = "DELETE FROM run_files WHERE run_id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($run_ids);
            }

            if (tableExists($pdo, 'proteins', $database)) {
                $sql = "DELETE FROM proteins WHERE run_id IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($run_ids);
            }

            $sql = "DELETE FROM runs WHERE run_id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($run_ids);

            $deleted_runs = count($run_ids);
        }

        $pdo->commit();
    }

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo <<<_HEAD1
<html>
<head>
    <title>Exit</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
</head>
<body>
_HEAD1;

    echo "<h1>Exit Error</h1>";
    echo "<p>There was a problem clearing your data: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><a href='pw_index.php'>Return to home page</a></p>";

    echo <<<_TAIL1
</body>
</html>
_TAIL1;
    exit();
}

$_SESSION = [];
session_destroy();

echo <<<_HEAD2
<html>
<head>
    <title>Exit</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
</head>
<body>
_HEAD2;

echo "<h1>You have been logged out</h1>";
echo "<p>Your session has been ended successfully.</p>";
echo "<p>Deleted non-example runs for this session: " . htmlspecialchars((string)$deleted_runs) . "</p>";
echo "<p>The example dataset has been preserved.</p>";
echo "<p><a href='pw.php'>Return to login page</a></p>";

echo <<<_TAIL2
</body>
</html>
_TAIL2;
?>
