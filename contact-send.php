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
