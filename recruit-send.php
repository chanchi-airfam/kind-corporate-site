<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require __DIR__ . '/mail-config.php';
require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

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

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
    $mail->addAddress($to);
    $mail->addReplyTo($email, $name);

    $mail->Subject = $mailSubject;
    $mail->Body    = $body;

    $mail->send();
    respond(true);
} catch (PHPMailerException $e) {
    respond(false, 'メール送信に失敗しました');
}
