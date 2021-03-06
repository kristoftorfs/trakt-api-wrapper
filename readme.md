Trakt-api-wrapper version 2
=================

This is the Trakt API wrapper for their new API (version 2). It's in active development

## Installation

In your composer.json file add:`"wubs/trakt": "dev-master"` and run `composer install`

## The goal

The goal of this wrapper is to make communicating with the Trakt api easier. It aims to be easy, readable and usable in many cases. Designed as a composer package it can be easy installed inside a lager application.

## OAuth

First, create a Trakt object with your client id, client secret and the redirect url:

```PHP
<?php
$trakt = new Trakt($clientId, $clientSecret, $redirectUrl);
```
 
The redirect url is where you have to specify the url Trakt is going to send you the code to obtain an access code. 
The  working of OAuth is that you request access form your app to Trakt with your id, secret and redirect url. This 
way, Trakt knows on what URL it can send you the code to get the access code. With the code you get from Trakt, you 
can request the accesscode by running `$trakt->getAccessCode($code);`
but, when you don’t have a website running yet, you can’t specify a return url (because trakt can’t access your 
local machine) to get the code displayed on the trakt site, you need to tell Trakt that you want to redirect to them
self. You can do this by providing the following string as redirect url: `urn:ietf:wg:oauth:2.0:oob`

So, lets say our client id is `12345678` and our client secret is `secret01` and we can't have a redirect url because
 you are developing locally (Trakt can't access your local dev environment). Now, `urn:ietf:wg:oauth:2.0:oob` is 
 going to be our redirect url. Creating the Trakt object can now be done like this:
 
 ```PHP
 <?php
 $trakt = new Trakt("12345678", "secret01", "urn:ietf:wg:oauth:2.0:oob");
 ```

Now you can create a file called `trakt.php` in the root of your project and put the following in it:

 ```PHP
<?php
use Wubs\Trakt\Trakt;

require "vendor/autoload.php";

$trakt = new Trakt("12345678", "secret01", "urn:ietf:wg:oauth:2.0:oob");
$trakt->authorize();
```

now run from the same locataion: `php -S 127.0.0.1:8000` and point your browser to `127.0.0.1:8000` This will start 
the authorization process and redirect you to the Trakt site let you log in and/or let you authorize the application.
When done, it'll display a page with a code. Lets say the code is `AaassSSfeAAsDf323AsdF4^h` Copy the code and change
the file `trakt.php` to this:
 
 ```PHP
<?php
require "vendor/autoload.php";

$trakt = new Trakt("12345678", "secret01", "urn:ietf:wg:oauth:2.0:oob");
$token = $trakt->getAccessToken("AaassSSfeAAsDf323AsdF4^h");
var_dump($code);
```

You can now run the file from the terminal and it'll display the contents of the `AccessToken` object.
store the values from the code somewhere (I use the .env php package for development of this package)
With the values you can re-create the `AccessToken` when you need it by using the `TraktAccessToken::ceate()` method 
and pass the required parameters `$token`, `$type`, `$expires`, `$refresh` and `$scope`.

Be aware that above approach is especially when you develop for your own. When your application needs user specific 
information and get/post/update stuff based on a user the approach is different. In this case you should use a 
redirect url to your application and handle getting the access token from there.


## The inner workings

This updated wrapper is in many ways different from the previous version. Inspired by other contributors I decided to
follow a more Object Oriented approach. Each request is a object on its own, and can have a response handler just 
or that request. This is the basis.

Currently I'm implementing all kinds of requests. It's not hard to do this yourself.

A request should extend the `AbstractRequest` class and implement the `AbstractRequest::getRequestType()` and 
`AbstractRequest::getUrl()`. methods To set the response handler use `AbstractRequest::setResponseHandler(new 
MyHandler())` inside the constructor of the request object. When you do not set the a handler the
`DefaultResponseHandler` will be used. 

The basics of a `Request` object will look like this.
```php
<?php

namespace Wubs\Trakt\Request\Calendars;

use Wubs\Trakt\Request\AbstractRequest;
use Wubs\Trakt\Request\RequestType;

class Movies extends AbstractRequest
{
    public function getRequestType()
    {
        return RequestType::GET;
    }
    public function getUrl()
    {
        return "calendars/movies/"
    }
}
```

Only, we don't have any way of passing the required parameters to the url. For the request class above this are a 
start date the number of days. These parameters are also wrapped in their own classes.

A parameter is just a representation of the value that is required. For example, the `StartDate` parameter ensures a 
format of `Y-m-d` when it's converted to a string. Also, a parameter should implement the `Parameter`  
implement the `Parameter` interface. 
    
For all requests that need a `StartDay` and a `Day` parameter, there is the `TimePeriod` trait that handles the 
assigning. wWhen there are no parameters provided it sets the standard value, as provided by the `Parameter::standard
()` method.

Now, the above class can be updated to use the `Parameter` classes.

```PHP
<?php

namespace Wubs\Trakt\Request\Calendars;

use Wubs\Trakt\Request\AbstractRequest;
use Wubs\Trakt\Request\Parameters\Days;
use Wubs\Trakt\Request\Parameters\StartDate;
use Wubs\Trakt\Request\RequestType;
use Wubs\Trakt\Request\TimePeriod;

class Movies extends AbstractRequest
{
    
    use TimePeriod;

    public function __construct(StartDate $startDate = null, Days $days = null)
    {
        parent::__construct();
        
        $this->setStartDate($startDate);
        $this->setDays($days);
    }

    public function getRequestType()
    {
        return RequestType::GET;
    }

    public function getUrl()
    {
        return "calendars/movies/:start_date/:days";
    }
}
```
 
A simple `calendars/movies` request can now preformed like so:

```php
<?php

use Wubs\Trakt\Request\Calendars\Movies;
use Wubs\Trakt\Request\Parameters\Days;
use Wubs\Trakt\Request\Parameters\StartDate;
use Wubs\Trakt\Token\TraktAccessToken;

$id = getenv("CLIENT_ID");

// this is the access code, unique for each user! Below is the code for when you just use it for yourself.
$token = TraktAccessToken::create(
                getenv("TRAKT_ACCESS_TOKEN"),  
                getenv("TRAKT_TOKEN_TYPE"),
                getenv("TRAKT_EXPIRES_IN"),
                getenv("TRAKT_REFRESH_TOKEN"),
                getenv("TRAKT_SCOPE")
            );
$parameters = [StartDate::standard(), Days::num(1)];

$response = Movies::request($id, $token, $parameters); //the parameters will be passed through to the request object
```

The `$response` variable will now contain the json response from the request.
 
If you want to manipulate the response before returning it from the `Movies::request()` method, you can create your 
own response handler by implementing the `Response` interface and write your manipulate code inside the 
`Response::handle(ResponseInterface $response)` method, where `ResponseInterface` is 
`GuzzleHttp\Message\ResponseInterface`

the `DefaultResponseHandler` looks like this:

```php

<?php

namespace Wubs\Trakt\Response;


use GuzzleHttp\Message\ResponseInterface;

class DefaultResponseHandler implements Response
{
    /**
     * @param ResponseInterface $response
     * @return mixed
     */
    public function handle(ResponseInterface $response)
    {
        return $response->json();
    }
}
```

You can create your own response handler, just extend `AbstractResponseHandler` and implement the `ResponseHandler` 
interface.

When calling a request statically the response handler can be passed after the request parameters like so:
 
 ```PHP
 <?php
 use Wubs\Trakt\Request\Calendars\Movies;
 
 class MyResponseHandler extends AbstractResponseHandler implements ResponseHandler
 {
 
     public function handle(ResponseInterface $response)
     {
         return true;
     }
 }
 
 $parameters = [StartDate::standard(), Days::num(1)];
 $response = Movies::Movies::request(get_client_id(), get_token(), $parameters, new MyResponseHandler());
 
 print_r($response); //true
 ```
 
When you build the request yourself, it can be set with `$request->setResponseHandler(new MyResponseHandler())`

Feel free to contact me or help development :)

[oauth2-client]: https://github.com/thephpleague/oauth2-client
