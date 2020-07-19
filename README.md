# practice-Selenium-with-PHP
Seleniumをphpで使う。動的サイトをスクレイピングする。

## 参考URL
http://yebisupress.dac.co.jp/2018/07/06/test_automation_with_selenium-x-php/


## selenium-server-standaloneをインストール。
`brew install selenium-server-standalone`

## 各ブラウザ用ドライバのインストール
* Selenium Google Chrome Driver
https://sites.google.com/a/chromium.org/chromedriver/
→ https://chromedriver.storage.googleapis.com/2.38/chromedriver_mac64.zip

## facebook-webdriverのインストール
composerで、インストール。
```Shell
$ php composer.phar require facebook/webdriver
```

`Could not open input file: composer.phar`の場合は。以下の通り。
* https://www.kakistamp.com/entry/2019/07/13/003852

```Shell
$ php -r "readfile('https://getcomposer.org/installer');" | php
```


## サーバー起動
`$ selenium-server -port 4444 &`
```Shell
$ selenium-server -port 4444 &
[1] 33415
C02SY1XFGTFJ:selenium username$ 18:04:14.512 INFO [GridLauncherV3.launch] - Selenium build info: version: '3.11.0', revision: 'e59cfb3'
18:04:14.513 INFO [GridLauncherV3$1.launch] - Launching a standalone Selenium Server on port 4444
2018-05-24 18:04:14.624:INFO::main: Logging initialized @441ms to org.seleniumhq.jetty9.util.log.StdErrLog
18:04:14.882 INFO [SeleniumServer.boot] - Welcome to Selenium for Workgroups....
18:04:14.882 INFO [SeleniumServer.boot] - Selenium Server is up and running on port 4444
```
で、下のようになればおｋ

```Shell
$ jobs
[1]+  Running                 selenium-server -port 4444 &
```

## phpファイルを実行。
```Shell
php test.php
```


## 終了時下のようにkillする。
```Shell
$ jobs
[1]+  Running                 selenium-server -port 4444 &  (wd: ~/selenium)
$ kill %1
$ jobs
[1]+  Exit 143                selenium-server -port 4444  (wd: ~/selenium)
```