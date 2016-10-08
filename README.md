# tapwarp-php-lib
A library that enable PHP websites receive image uploads directly from smartphone app.

## Synopsis
This is a php library that enables very easy adoption of smartphone image-uploading into websites.  This library is the server side.  You will need the Tap Warp smartphone app as the client side.
And Tap Warp is freely available from either [App Store](https://itunes.apple.com/us/app/tap-warp/id1137457615?mt=8) for iOS for [Play Store](https://play.google.com/store/apps/details?id=net.qaon.tapwarp) for Android.

## Idea
As a user prepares photos for something, e.g., a merchandize, he takes photos and upload to a website.  In this process, it would be convenient to take the photos for a merchandize and upload for it directly, rather than take photos, open the website, sign in, browse to an upload page, open the upload form, browse through photos, select the photo, tap the upload button.  Tap Warp is just such an app that save you all the intermediate steps.  With Tap Warp, you take photos and then scan a QR code on the website, the photos will be uploaded.  Very succinct.

However, Tap Warp is an app at users' hands, a server needs to accept and properly process data sent from the app.  This project is a simple solution for the server side.  With tapwarp-php-lib, you can very quickly implement a server side for the Tap Warp app in minutes, at least with PHP.

## Code Example 1
To an extreme, the following code may be all that you need for implementing image upload.

```
<?php

require "lib/tapwarp/tapwarp.php";

$tw=new TapWarp(new DefaultTapWarpHandler());
$tw->serveHandlerObj->setSavePath("path/to/image/folder");
$tw->serve();
```

### Explanation
```
require "lib/tapwarp/tapwarp.php";
```
This grasps in the tapwarp library codes, so that you can call necessary functions later.

```
$tw=new TapWarp(new DefaultTapWarpHandler());
```
Initializes a new TapWarp instance with the default handler.  
`DefaultTapWarpHandler` is the default handler.  It handles various things, such as creating folders, saving pictures and videos to specified folders, and call various callbacks for notifying events.  When you want something that suits your special needs, this is probably the class that you extend.
`TapWarp` is the entry point class.  It handles requests from clients.

`$tw->serveHandlerObj` retrieves the instance by `new DefaultTapWarpHandler()`.  `setSavePath("path/to/image/folder")` sets where images will be saved.

It is strongly recommended that you use a separate folder for all images that tapwarp-php-lib receives.

## Code Example 2
To provide a QR code for the Tap Warp app, just create a JSON code and convert to a QR code.

A JSON code looks like the following:
```
{
    "authKey":"9378aed038a4e9b9ddda909ce1780b70734e88a1ba14f0c6dbfdcef76e423bb8",
    "realm":"Tap Warp Demo",
    "targetUrl":"http:\/\/twdemo.qaon.ofc\/demo001\/imageupload.php",
    "sizeHint":"640x480*",
    "targetSize":"640x640",
    "clientData":{
        "storeid":"a0113f4b57ce09d65b6b6214845175e7"
    },
    "appKey":"40b6f3838fca651a4ebf1f4d56ad0613"
} 
```
The use any program you like to convert to a QR code.  For example, [phpqrcode](http://phpqrcode.sourceforge.net/) would be a nice choice, just something like:
```
$jsontext=json_encode($some_php_object);
QRcode::png($jsontext);
```

### Explanation

In the above JSON code, `authKey` and `targetUrl` are required, they provides authentication information and the URL to which image data are sent, respectively.

The `authKey` is a 64~128 byte ascii text consists of alphanumeric characters only.  It is often randomly generated, so that a correct `authKey` is very very difficult to be guessed.

The `targetUrl` is specify an entry point on your server so that Tap Warp will send data to it.  This URL is generally where you call tapwarp-php-lib codes.

All other fields are optional.  Explanation of them can be found at the [The Target Info API doc](http://twdemo.qaon.net/index.php?r=docs/target-info-api).

## Online Demonstration

### Tap Warp Demo
This website is often used to demonstrate the newest functionalities.  But very simple.

[http://twdemo.qaon.net](http://twdemo.qaon.net)

### FREEPHOTO @ QAON
This is the young free photo sharing website, specialized to Tap Warp.  It has no upload form, the only way to upload photos is to use the Tap Warp app.

[http://freephoto.qaon.net](http://freephoto.qaon.net)

## Installation
Just
```
git clone https://github.com/qaon/tapwarp-php-lib.git
```
or download and unpack a zip archive, then include `tapwarp.php` into your program.

## API Reference
Detailed API reference of the protocol that tapwarp-php-lib uses is detailed at the [API pages](http://twdemo.qaon.ofc/index.php?r=docs).

## License
Licensed under GPLv3.

