# laravel-breeze-api

[Breeze](https://github.com/laravel/breeze/)をベースとしたAPIエンドポイントを簡単に実装するためのパッケージです。  

APIのみを提供するため、BreezeのViewに関するファイル（blade、javascript、css）を持ちません。  

また、[Sanctum](https://github.com/laravel/sanctum)と併用して、SPAでのセッションによる認証とトークンによる認証を実装したものです。

## setup
```
$ laravel new my-app

$ cd my-app

$ composer require nrikiji/breeze-api

$ php artisan breeze-api:install
```

次にsanctumをインストールします
```
$ composer require laravel/sanctum

$ php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

$ php artisan migrate
```

APIトークンを使用するためにUserモデルでHasApiTokensトレイトを利用します  

app/Models/User.php
```
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

Sanctumとセッションを使用するためのミドルウェアをAPIに追加します

app/Http/Kernel.php
```
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,

        # 以下３つを追加
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Session\Middleware\StartSession::class,
    ],
];
```

### corsが必要な場合

※ 例  
バックエンド : http://localhost:8000  
フロントエンド : http://localhost:3000  

.env
```
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

app/config/cors.php
```
'supports_credentials' => true,
```

### ユーザーメールアドレス認証を有効にする場合

MustVerifyEmailインターフェースを実装する

app/Models/User.php
```
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
  ・・・
}
```

### メール送信が必要な場合

メールアドレス検証、パスワードリセット機能で使用します  

メールサーバーを設定します  

.env
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=465
MAIL_USERNAME=mail_username
MAIL_PASSWORD=mail_password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

確認用のメールに記載されるリンクのURLを設定します。  

app/Http/Providers/AuthServiceProvider.php
```
// メールアドレス確認用のURL
VerifyEmail::createUrlUsing(function ($notifiable) {
    $id = $notifiable->getKey();
    $hash = sha1($notifiable->getEmailForVerification());

    ・・・
    
    // このURLがメールにリンクとして挿入されます。idとhashを使用して、$endpointへリクエストします
    $parsed = parse_url($endpoint);

    $url = "https://example.com" . $parsed["path"] . "?" . $parsed["query"];

    return $url;
});

// パスワードリセット用のURL
ResetPassword::createUrlUsing(function ($user, string $token) {
    // このURLがメールにリンクとして挿入されます。tokenを使用して、APIへリクエストします
    return 'https://example.com/reset-password?token=' . $token;
});
```

## 使い方

### APIトークン

ユーザー登録
```
$ curl -X POST -H 'Accept: application/json' http://localhost:8000/api/register -d 'name=hoge' -d 'email=hoge@example.com' -d 'password=password' -d 'password_confirmation=password'                             
{"token":"1|xxxxxxxxxx"}
```

ユーザーメールアドレス検証用URL再送信
```
curl -X POST -H 'Accept: application/json' -H 'Authorization: Bearer 1|xxxxxxxxxx' "http://localhost:8000/api/email/verification-notification"
```

ユーザーメールアドレス検証  
※エンドポイントはメール本文のURLから判定
```
$ curl -X GET -H 'Accept: application/json' -H 'Authorization: Bearer 1|xxxxxxxxxx' "http://localhost:8000/api/verify-email/123/yyyyyyyyyy?expires=1623304571&signature=zzzzzzzzzz"
```

ログイン
```
$ curl -X POST -H 'Accept: application/json' http://localhost:8000/api/login -d 'email=hoge@example.com' -d 'password=password' 
{"token":"3|xxxxxxxxxx"}
```

ユーザー情報取得
```
$ curl -X GET -H 'Accept: application/json' -H 'Authorization: Bearer 1|xxxxxxxxxx' http://localhost:8000/api/user
{"id":1,"name":"hoge","email":"hoge@example.com","email_verified_at":null,"created_at":"2021-06-10T02:34:45.000000Z","updated_at":"2021-06-10T02:34:45.000000Z"}
```

ログアウト
```
$ curl -X POST -H 'Accept: application/json' -H 'Authorization: Bearer 1|xxxxxxxxxx' http://localhost:8000/api/logout
```

パスワードリセット
```
$ curl -X POST -H 'Accept: application/json' http://localhost:8000/api/forgot-password -d "email=hoge@example.com"
```

パスワードリセット2  
※ tokenは確認メールの本文から取得
```
$ curl -X POST -H 'Accept: application/json' http://localhost:8000/api/reset-password -d "email=hoge@example.com" -d "password=password" -d "password_confirmation=password" -d "token=xxxxxxxxxx"
```

### SPA

axiosの例  
corsの場合、withCredentials=trueとする
```
axios.defaults.withCredentials = true;
```

ユーザー登録
```
await axios.get(API_URL + 'sanctum/csrf-cookie');
await axios.post(API_URL + 'api/register',{
  name: "hoge",
  email: "hoge@example.com",
  password: "password",
  password_confirmation: "password",
});
```

ユーザーメールアドレス検証用URL再送信
```
await axios.post(API_URL + 'email/verification-notification');
```

ユーザーメールアドレス検証  
※エンドポイントはメール本文のURLから判定
```
const path = "api/verify-email/123/xxxxxxxxxx?expires=1623206775&signature=yyyyyyyyyy";                         
await axios.get(API_URL + path);
```

ログイン
```
await axios.get(API_URL + 'sanctum/csrf-cookie');
await axios.post(API_URL + 'api/login', {　email: "hoge@example.com",　password: "password" });
```

ユーザー情報取得
```
const user = await axios.get(API_URL + 'api/user');
console.log(user); // => {id: 1, name: "hoge", email: "hoge@example.com", email_verified_at: null,…}
```

ログアウト
```
await axios.post(API_URL + 'api/logout');
```

パスワードリセット
```
await axios.post(API_URL + 'api/forgot-password', { email: "hoge@example.com" });
```

パスワードリセット2  
※ 確認メールの本文から取得
```
await axios.post(API_URL + 'api/reset-password', {
  email: "hoge@example.com",
  password: "password",
  password_confirmation: "password",
  token: "xxxxxxxxxx",
});
```

## トラブルシュート
### APIのレスポンスがJSONでなくHTML（テキスト）になってしまう

特にAPIトークン認証の際に起こることがあります。HTTPリクエストヘッダーに「Accept: application/json」を追加してください。LaravelではAjaxによるリクエストやこのヘッダーが指定されているときにレスポンスをJSONとしようとします。  

ただし、このヘッダーをつけることができない場合のためにHandleAuthApiRequestsミドルウェアを用意しました。適宜、ご利用ください。これは、app/Http/Kernel.php に追加することで有効になります。

## リンク
- [https://github.com/laravel/breeze/](https://github.com/laravel/breeze/)
- [https://github.com/laravel/sanctum](https://github.com/laravel/sanctum)
