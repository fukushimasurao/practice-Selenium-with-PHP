<?php
require './vendor/autoload.php';

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverDimension;

// 検索したいワードを$wordに入れる。
$word = 'amazon';
$title = "$word - Buy $word at Best Price in Philippines | www.lazada.com.ph";

// chrome利用用意
$options = new ChromeOptions();
$options->addArguments(['--headless']);
$options->addArguments(["window-size=1024,2048"]);

$host = 'http://localhost:4444/wd/hub';
$capabilities = Facebook\WebDriver\Remote\DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$driver = Facebook\WebDriver\Remote\RemoteWebDriver::create($host, $capabilities);
$driver->get('https://www.lazada.com.ph/');
$browserLogs = $driver->manage()->getLog('browser');
$element = $driver->findElement(WebDriverBy::name('q'));
$element->sendKeys($word);
$element->submit();

// 商品画像を全部表示するため、画面を少し右に移動
$driver->executeScript("window.scrollTo(300, 0);");
$driver->wait(15)->until(
    WebDriverExpectedCondition::titleIs($title)
);
if ($driver->getTitle() !== "$title") {
    throw new Exception('fail');
}

/**
 * 必要なもの
 * 商品ごとのURL
 * 写真
 * タイトル。
 */
// $itemUrls = $driver->findElements(WebDriverBy::cssSelector('.c5TXIP .c2iYAv .cRjKsc'));
$photos = $driver->findElements(WebDriverBy::cssSelector('.c5TXIP .c2iYAv .cRjKsc .c1ZEkM'));
$productNames = $driver->findElements(WebDriverBy::cssSelector('.c5TXIP .c3KeDq .c16H9d')); //->text

$items = [];
// 商品あるかチェック
if (count($photos) < 0) {
    throw new Exception('no item.');
}

foreach ($photos as $k => $v) {
    if ($k === 10) {
        break;
    }
    $items[$k]['photoUrl'] = $v->getAttribute('src');
}

foreach ($productNames as $k => $v) {
    if ($k === 10) {
        break;
    }
    $items[$k]['titleName'] = $v->getText();
}

print_r($items);
echo "\n";

$file = "サンプル_chrome.png";
$driver->takeScreenshot($file);
$driver->close();
