# laravel-breeze-api

This is a package for easy implementation of API endpoints based on [Breeze](https://github.com/laravel/breeze).  

Since it provides only API, it does not have any files (blade, javascript, css) related to Breeze's View.  

It is also used in conjunction with [Sanctum](https://github.com/laravel/sanctum) to implement authentication by session and authentication by token in SPA.

## setup
```
$ laravel new my-app

$ cd my-app

$ composer require nrikiji/breeze-api

$ php artisan breeze-api:install
```

Next, install sanctum
```
$ composer require laravel/sanctum

$ php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

$ php artisan migrate
```

Use the HasApiTokens trate in the User model to use API tokens  

app/Models/User.php
```
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

Add middleware to the API to use Sanctum and sessions

app/Http/Kernel.php
```
protected $middlewareGroups = [
    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,

        # Added the following three items
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Session\Middleware\StartSession::class,
    ],
];
```

### If cors is required

※   
backend : http://localhost:8000  
frontend : http://localhost:3000  

.env
```
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

app/config/cors.php
```
'supports_credentials' => true,
```

### To enable user email authentication

Implement the MustVerifyEmail interface

app/Models/User.php
```
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
  ・・・
}
```

### If you need to send an email

Used for email address verification and password reset functions  

Configure the mail server  

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

Set the URL for the link that will be included in the confirmation email.  

app/Http/Providers/AuthServiceProvider.php
```
// URL for email address verification
VerifyEmail::createUrlUsing(function ($notifiable) {
    $id = $notifiable->getKey();
    $hash = sha1($notifiable->getEmailForVerification());

    ・・・

    // This URL will be inserted as a link in the email, using the id and hash to request the $endpoint
    $parsed = parse_url($endpoint);

    $url = "https://example.com" . $parsed["path"] . "?" . $parsed["query"];

    return $url;
});

// URL for password reset
ResetPassword::createUrlUsing(function ($user, string $token) {
    // This URL will be inserted as a link in the email, and the token will be used to request the API
    return 'https://example.com/reset-password?token=' . $token;
});
```

## Usage

### API Token

User Registration
```
$ curl -v -X POST -H 'Accept: application/json' http://localhost:8000/api/register -d 'name=hoge' -d 'email=hoge@example.com' -d 'password=password' -d 'password_confirmation=password'                             
{"token":"1|xxxxxxxxxx"}
```

Resend URL for user email address verification
```
curl -v -X POST -H 'Accept: application/json' -H 'Authorization: Bearer xxxxxxxxxx' "http://localhost:8000/api/email/verification-notification"
```

User email address verification  
*The endpoint is determined from the URL in the body of the email.
```
$ curl -v -X GET -H 'Accept: application/json' -H 'Authorization: Bearer xxxxxxxxxx' "http://localhost:8000/api/verify-email/123/yyyyyyyyyy?expires=1623304571&signature=zzzzzzzzzz"
```

Login
```
$ curl -v -X POST -H 'Accept: application/json' http://localhost:8000/api/login -d 'email=hoge@example.com' -d 'password=password' 
{"token":"3|xxxxxxxxxx"}
```

User information
```
$ curl -v -X GET -H 'Accept: application/json' -H 'Authorization: Bearer xxxxxxxxxx' http://localhost:8000/api/user
{"id":1,"name":"hoge","email":"hoge@example.com","email_verified_at":null,"created_at":"2021-06-10T02:34:45.000000Z","updated_at":"2021-06-10T02:34:45.000000Z"}
```

Logout
```
$ curl -v -X POST -H 'Accept: application/json' -H 'Authorization: Bearer xxxxxxxxxx' http://localhost:8000/api/logout
```

Password reset
```
$ curl -v -X POST -H 'Accept: application/json' http://localhost:8000/api/forgot-password -d "email=hoge@example.com"
```

Password reset2  
* Get the token from the body of the confirmation email.
```
$ curl -v -X POST -H 'Accept: application/json' http://localhost:8000/api/reset-password -d "email=hoge@example.com" -d "password=password" -d "password_confirmation=password" -d "token=xxxxxxxxxx"
```

### SPA

axios example  
for cors, set withCredentials=true
```
axios.defaults.withCredentials = true;
```

User Registration
```
await axios.get(API_URL + 'sanctum/csrf-cookie');
await axios.post(API_URL + 'api/register',{
  name: "hoge",
  email: "hoge@example.com",
  password: "password",
  password_confirmation: "password",
});
```

Resend URL for user email address verification
```
await axios.post(API_URL + 'email/verification-notification');
```

User email address verification  
*The endpoint is determined from the URL in the body of the email.
```
const path = "api/verify-email/123/xxxxxxxxxx?expires=1623206775&signature=yyyyyyyyyy";                         
await axios.get(API_URL + path);
```

Login
```
await axios.get(API_URL + 'sanctum/csrf-cookie');
await axios.post(API_URL + 'api/login', {　email: "hoge@example.com",　password: "password" });
```

User information
```
const user = await axios.get(API_URL + 'api/user');
console.log(user); // => {id: 1, name: "hoge", email: "hoge@example.com", email_verified_at: null,…}
```

Logout
```
await axios.post(API_URL + 'api/logout');
```

Password reset
```
await axios.post(API_URL + 'api/forgot-password', { email: "hoge@example.com" });
```

Password reset2  
* Get the token from the body of the confirmation email.
```
await axios.post(API_URL + 'api/reset-password', {
  email: "hoge@example.com",
  password: "password",
  password_confirmation: "password",
  token: "xxxxxxxxxx",
});
```

## trouble shooting
### API responses become HTML (text) instead of JSON.

Add "Accept: application/json" to the HTTP request header. Laravel will try to make the response JSON when using Ajax requests or when this header is specified. Laravel will try to make the response JSON when requesting via Ajax or when this header is specified.  

However, we have prepared HandleAuthApiRequests middleware for cases where it is not possible to add this header. Please use it as appropriate. This can be enabled by adding it to app/Http/Kernel.php.

## Link
- [https://github.com/laravel/breeze/](https://github.com/laravel/breeze/)
- [https://github.com/laravel/sanctum](https://github.com/laravel/sanctum)
