/Users/fukushimahayato/Desktop/rank_king/goo/resources/views/user/layout/topicks_warning.blade.php

<!DOCTYPE html>
<html>
<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex,follow">
<link href="{{service('icon')}}" type="image/x-icon" rel="shortcut icon" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>404 Not Found.お探しのページが見つかりませんでした。</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
@yield('meta')
@include('user.elements.layouts.css.cssManager')
@include('user.elements.layouts.head.headManager')
@if(isService('gooranking'))
<link rel="stylesheet" href="//ranking.xgoo.jp/cdn/v2/css/common.css?20190124">
<link rel="stylesheet" href="//u.xgoo.jp/css/min/1.5.css">
@endif
<script data-ad-client="ca-pub-4141086125547997" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
</head>
<body class="{{service('name')}} @if(isMobile()) sp @else pc @endif @if(isService('gooranking')) NR-col2a NR-rwd @endif">
@include('user.elements.Common.header',['_h1'=>true])
<div id="wrapper" class="container clearfix" style="margin-bottom:30px;min-height: 300px;">
    @yield('content')
</div>
@include('user.elements.Common.footer')

</body>
</html>
