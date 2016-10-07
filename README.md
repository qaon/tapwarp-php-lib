# tapwarp-php-lib
A library that enable PHP websites receive image uploads directly from smartphone app.

## Synopsis
This is a php library that enables very easy adoption of smartphone image-uploading into websites.  This library is the server side.  You will need the Tap Warp smartphone app as the client side.
And Tap Warp is freely available from either [App Store](https://itunes.apple.com/us/app/tap-warp/id1137457615?mt=8) for iOS for [Play Store](https://play.google.com/store/apps/details?id=net.qaon.tapwarp) for Android.

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

## Online Demonstration

### Tap Warp Demo
This website is often used to demonstrate the newest functionalities.  But very simple.

### FREEPHOTO @ QAON
This is the young free photo sharing website, specialized to Tap Warp.  It has no upload form, the only way to upload photos is to use the Tap Warp app.

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

