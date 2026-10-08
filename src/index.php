<?php

require __DIR__ . '/lib/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = new Logger('app');
$log->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

try {
    $dsn = 'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME');

    # DBアクセスオブジェクト作成
    $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'add') {
        # 新規タスク追加
        $sql = "INSERT INTO task (name, memo, author, assignee) VALUES (:name, :memo, :author, :assignee)";
        $sth = $pdo -> prepare($sql);
        $sth -> bindValue(':name', $_POST['task_name']);
        $sth -> bindValue(':memo', $_POST['task_memo']);
        $sth -> bindValue(':author', $_POST['task_author']);
        $sth -> bindValue(':assignee', $_POST['task_assignee']);
        $sth -> execute();

        # 一覧表示
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    # 共通処理
    # タスク一覧取得
    $tasks = $pdo->query("SELECT id, name, memo, author, assignee, to_char(create_date, 'YYYY/MM/DD HH24:MI:SS') create_date, to_char(update_date, 'YYYY/MM/DD HH24:MI:SS') update_date FROM task ORDER BY update_date desc")->fetchAll(PDO::FETCH_ASSOC);
    $log->info('tasks fetched', ['count' => count($tasks)]);

} catch (PDOException $e) {
    $log->error('DB接続エラー', ['message' => $e->getMessage()]);
    http_response_code(500);
    exit('DB接続エラー');
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>docker-php-postgres</title>
    <link rel="stylesheet" href="css/style.css" type="text/css" />
</head>
<body>
    <div>
        <h1>タスク一覧</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>タスク名</th>
                    <th>内容</th>
                    <th>作成者</th>
                    <th>担当者</th>
                    <th>作成日</th>
                    <th>更新日</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tasks as $task): ?>
                <tr>
                    <td><?= htmlspecialchars($task['id'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($task['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($task['memo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($task['author'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($task['assignee'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($task['create_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($task['update_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>編集</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <br>
    <br>
    <div>
        <form method="POST">
            <div>
                <label for="task_name">タスク名</label>
                <input type="text" name="task_name" id="task_name">
            </div>
            <div>
                <label for="task_memo">内　容</label>
                <input type="text" name="task_memo" id="task_memo">
            </div>
            <div>
                <label for="task_author">作成者</label>
                <input type="text" name="task_author" id="task_author">
            </div>
            <div>
                <label for="task_assignee">担当者</label>
                <input type="text" name="task_assignee" id="task_assignee">
            </div>
            <div>
                <button name="action" value="add">新規登録</button>
            </div>
        </form>
    </div>
</body>
</html>
