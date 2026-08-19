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

$companyName = cleanInput($_POST['company-name'] ?? '');
$name        = cleanInput($_POST['name'] ?? '');
$email       = cleanInput($_POST['email'] ?? '');
$tel         = cleanInput($_POST['tel'] ?? '');
$subject     = cleanInput($_POST['subject'] ?? '');
$message     = trim(str_replace("\r\n", "\n", $_POST['message'] ?? ''));

// 必須項目チェック
if ($companyName === '' || $name === '' || $email === '' || $message === '') {
    respond(false, '必須項目が入力されていません');
}

// メールアドレス形式チェック
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'メールアドレスの形式が正しくありません');
}

mb_language('Japanese');
mb_internal_encoding('UTF-8');

$mailSubject = '【KIND HP】お問い合わせ（' . $companyName . '）';

$body = "ウェブサイトのお問い合わせフォームより送信がありました。\n\n";
$body .= "----------------------------------------\n";
$body .= "会社名　　　：{$companyName}\n";
$body .= "ご担当者名　：{$name}\n";
$body .= "メールアドレス：{$email}\n";
$body .= "電話番号　　：" . ($tel !== '' ? $tel : '（未入力）') . "\n";
$body .= "ご相談内容　：" . ($subject !== '' ? $subject : '（未選択）') . "\n";
$body .= "----------------------------------------\n";
$body .= "お問い合わせ内容：\n{$message}\n";

$headers = 'From: website@kind-build.co.jp' . "\r\n";
$headers .= 'Reply-To: ' . $email;

$sent = mb_send_mail($to, $mailSubject, $body, $headers);

if ($sent) {
    respond(true);
} else {
    respond(false, 'メール送信に失敗しました');
}
