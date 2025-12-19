<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>メールアドレスの認証</title>
</head>
<body style="font-family: 'Helvetica Neue', Helvetica, Arial, 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 30px; border-radius: 8px;">
        <h1 style="color: #333; font-size: 24px; margin-bottom: 20px;">メールアドレスの認証をお願いします</h1>
        
        <p style="margin-bottom: 20px;">
            {{ $user->name }} 様
        </p>
        
        <p style="margin-bottom: 20px;">
            この度は、CT COACHTECHにご登録いただき、ありがとうございます。<br>
            メールアドレスの認証を完了するため、以下のボタンをクリックしてください。
        </p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $verificationUrl }}" 
               target="_self"
               style="display: inline-block; background-color: #007bff; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                メールアドレスを認証する
            </a>
        </div>
        
        <p style="margin-bottom: 20px; font-size: 14px; color: #666;">
            ボタンがクリックできない場合は、以下のURLをブラウザのアドレスバーにコピーしてアクセスしてください。
        </p>
        
        <p style="margin-bottom: 20px; font-size: 12px; color: #999; word-break: break-all;">
            {{ $verificationUrl }}
        </p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #999; border-top: 1px solid #ddd; padding-top: 20px;">
            このメールは、CT COACHTECHへの会員登録時に自動送信されました。<br>
            心当たりがない場合は、このメールを無視してください。<br>
            この認証リンクは24時間有効です。
        </p>
    </div>
</body>
</html>

