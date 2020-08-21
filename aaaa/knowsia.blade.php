/Users/fukushimahayato/Desktop/rank_king/goo/resources/views/user/layout/knowsia.blade.php

<!DOCTYPE html>
<html>
<head prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# article: http://ogp.me/ns/article#">
    @yield('meta')
    @include('user.elements.layouts.css.cssManager')
    @include('user.elements.layouts.head.headManager')
    <script data-ad-client="ca-pub-4141086125547997" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
</head>
<body class="{{isService()}} @if(isMobile()) sp @else pc @endif NR-col2a NR-rwd" id="page-top">
    @include('user.elements.layouts.body.prepend')
    <div id="normal_view">
        @if(isMobile())<div id="overlay"></div>@endif
        @include('user.elements.Common.header',["_h1" =>(isController('index') && isAction('index'))])
        @yield('before')
        <div id="wrapper" class="container clearfix">
            <div id="row-main" class="row clearfix">
                @yield('content')
            </div>
            @yield('edit_content')
        </div>
        @yield('after')
        @if(isMobile())
            @include('user.elements.SP.search')
        @endif
        @include('user.elements.Common.footer')
    </div><!-- normal_view -->
    @if(isMobile())
        @include('user.elements.SP.menu')
    @endif
    @include('user.elements.layouts.bodyEnd.bodyEndManager')
</body>
</html>
