<?php
// DBに接続
$dbh = new PDO('mysql:host=mysql;dbname=example_db;charset=utf8', 'root', '');

// フォーム入力がすべて揃っている場合のみ処理
if (!empty($_POST['name']) && !empty($_POST['email']) && !empty($_POST['password'])) {

  // 1️⃣ メールアドレスの重複チェック
  $check_sth = $dbh->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
  $check_sth->execute([':email' => $_POST['email']]);
  $count = $check_sth->fetchColumn();

  if ($count > 0) {
    // 既に登録済みの場合
    echo "<p style='color:red;'>既にこのメールアドレスは登録されています。</p>";
  } else {
    // 2️⃣ 新規登録処理
    $insert_sth = $dbh->prepare("
      INSERT INTO users (name, email, password)
      VALUES (:name, :email, :password)
    ");
    $insert_sth->execute([
      ':name' => $_POST['name'],
      ':email' => $_POST['email'],
      ':password' => $_POST['password'],
    ]);

    // 登録完了後にリダイレクト
    header("HTTP/1.1 303 See Other");
    header("Location: ./signup_finish.php");
    exit;
  }
}
?>

<h1>会員登録</h1>
<!-- 登録フォーム -->
<form method="POST">
  <label>
    名前:
    <input type="text" name="name" required>
  </label>
  <br>
  <label>
    メールアドレス:
    <input type="email" name="email" required>
  </label>
  <br>
  <label>
    パスワード:
    <input type="password" name="password" minlength="6" autocomplete="new-password" required>
  </label>
  <br>
  <button type="submit">決定</button>
</form>

