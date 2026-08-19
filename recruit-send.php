<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

// 送信先メールアドレス
$to = 'd.asada@kind-build.co.jp';

function respond(bool $success, string $message = ''): void {
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'invalid method');
}

// メールヘッダーインジェクション対策：改行を除去
function cleanInput(string $value): string {
    $value = str_replace(["\r", "\n"], '', $value);
    return trim($value);
}

$name       = cleanInput($_POST['recruit-name'] ?? '');
$kana       = cleanInput($_POST['recruit-kana'] ?? '');
$birthday   = cleanInput($_POST['recruit-birthday'] ?? '');
$tel        = cleanInput($_POST['recruit-tel'] ?? '');
$email      = cleanInput($_POST['recruit-email'] ?? '');
$position   = cleanInput($_POST['recruit-position'] ?? '');
$experience = cleanInput($_POST['recruit-experience'] ?? '');
$message    = trim(str_replace("\r\n", "\n", $_POST['recruit-message'] ?? ''));

// 必須項目チェック
if ($name === '' || $tel === '' || $email === '' || $message === '') {
    respond(false, '必須項目が入力されていません');
}

// メールアドレス形式チェック
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'メールアドレスの形式が正しくありません');
}

mb_language('Japanese');
mb_internal_encoding('UTF-8');

$mailSubject = '【KIND HP】採用応募（' . $name . '）';

$body = "採用応募フォームより送信がありました。\n\n";
$body .= "----------------------------------------\n";
$body .= "お名前　　　：{$name}\n";
$body .= "フリガナ　　：" . ($kana !== '' ? $kana : '（未入力）') . "\n";
$body .= "生年月日　　：" . ($birthday !== '' ? $birthday : '（未入力）') . "\n";
$body .= "電話番号　　：{$tel}\n";
$body .= "メールアドレス：{$email}\n";
$body .= "希望職種　　：" . ($position !== '' ? $position : '（未選択）') . "\n";
$body .= "経験・保有資格：" . ($experience !== '' ? $experience : '（未入力）') . "\n";
$body .= "----------------------------------------\n";
$body .= "志望動機・自己PR：\n{$message}\n";

$headers = 'From: website@kind-build.co.jp' . "\r\n";
$headers .= 'Reply-To: ' . $email;

$sent = mb_send_mail($to, $mailSubject, $body, $headers);

if ($sent) {
    respond(true);
} else {
    respond(false, 'メール送信に失敗しました');
}
