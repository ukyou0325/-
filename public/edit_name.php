<?php
// セッションの取得とRedis接続
$session_cookie_name = 'session_id';
$session_id = $_COOKIE[$session_cookie_name] ?? base64_encode(random_bytes(64));
if (!isset($_COOKIE[$session_cookie_name])) {
  setcookie($session_cookie_name, $session_id);
}

$redis = new Redis();
$redis->connect('redis', 6379);

$redis_session_key = "session-" . $session_id;
$session_values = $redis->exists($redis_session_key)
  ? json_decode($redis->get($redis_session_key), true)
  : [];

// ログインチェック
if (empty($session_values['login_user_id'])) {
  header("HTTP/1.1 302 Found");
  header("Location: ./login.php");
  exit;
}

// DB接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db;charset=utf8', 'root', '');

// 現在のユーザー情報を取得
$sth = $dbh->prepare("SELECT * FROM users WHERE id = :id");
$sth->execute([':id' => $session_values['login_user_id']]);
$user = $sth->fetch();

if (!$user) {
  header("HTTP/1.1 302 Found");
  header("Location: ./login.php");
  exit;
}

// POSTされた場合（＝名前変更処理）
if (!empty($_POST['name'])) {
  $new_name = trim($_POST['name']);
  if ($new_name === '') {
    $error_message = '名前を空にすることはできません。';
  } else {
    // 名前を更新
    $update_sth = $dbh->prepare("UPDATE users SET name = :name WHERE id = :id");
    $update_sth->execute([
      ':name' => $new_name,
      ':id' => $user['id']
    ]);

    // 更新完了後、自分自身を再読み込み
    header("HTTP/1.1 303 See Other");
    header("Location: ./edit_name.php?updated=1");
    exit;
  }
}
?>

<h1>名前の変更</h1>

<?php if (!empty($_GET['updated'])): ?>
  <p style="color:green;">名前を変更しました！</p>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
  <p style="color:red;"><?= htmlspecialchars($error_message) ?></p>
<?php endif; ?>

<form method="POST">
  <label>
    新しい名前：
    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
  </label>
  <br>
  <button type="submit">変更する</button>
</form>

<hr>
<p>
  <a href="./login_finish.php">マイページへ戻る</a>
</p>

