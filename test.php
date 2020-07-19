<?php
require './vendor/autoload.php';

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverDimension;

$word = 'amazon';
$title = "$word - Buy $word at Best Price in Philippines | www.lazada.com.ph";

$options = new ChromeOptions();
$options->addArguments(['--headless']);
$host = 'http://localhost:4444/wd/hub';
$capabilities = Facebook\WebDriver\Remote\DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$driver = Facebook\WebDriver\Remote\RemoteWebDriver::create($host, $capabilities);
$driver->get('https://www.lazada.com.ph/');
$browserLogs = $driver->manage()->getLog('browser');
$element = $driver->findElement(WebDriverBy::name('q'));
$element->sendKeys($word);
$element->submit();


$driver->executeScript("window.scrollTo(300, 330);");

// className('c3gNPq')が見られるまで待機。
$driver->wait(1000)->until(
    WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::className('c3gNPq'))
);

if ($driver->getTitle() !== "$title") {
    throw new Exception('fail');
}

// $dimension = new WebDriverDimension(1920, 1080); // width, height
// $driver->manage()->window()->setSize($dimension);



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
if (count($photos) < 0) {
    throw new Exception('no photos.');
}

// foreach ($itemUrls as $k => $v) {
//     if ($k === 3) {
//         break;
//     }
//     // $name = $v->getAttribute('src');
//     // $items[$k]['itemUrl'] = $v->getAttribute('href');
//     // $items[$k]['itemUrl'] = $v->getText();

//     $name = $v->getText();
//     echo ($name);
//     echo "\n";
// }

// print_r($items);
// foreach ($itemUrl as $k => $v) {
//     if ($k === 10) {
//         break;
//     }
//     // $name = $v->getAttribute('src');
//     $items[$k]['itemUrl'] = $v->getAttribute('href');
// }


foreach ($photos as $k => $v) {
    if ($k === 10) {
        break;
    }
    $items[$k]['photoUrl'] = $v->getAttribute('src');
}

// // 空白のvalurがあれば、削除
// function myFilter($val)
// {
//     return !is_null($val);
// }

// // TODO:keyの再配列が必要かも。
// $items = array_filter($items, 'myFilter');
// var_dump($items);
// echo "\n";
// $driver->close();
// ----------------------------------------
// title

// title
if (count($productNames) >= 10) {
    foreach ($productNames as $k => $v) {
        if ($k === 10) {
            break;
        }
        $items[$k]['titleName'] = $v->getText();
    }
}
print_r($items);
echo "\n";

// else {
//     $productName = $v->getText();
//     echo $productName;
//     echo "\n";
// }

// $items = [];
// foreach ($title as $k => $v) {
//     if ($k === 10) {
//         break;
//     }
//     $items[] = $v->getAttribute('src');
// }

// // 空白のvalurがあれば、削除
// function myFilter($val)
// {
//     return !is_null($val);
// }

// TODO:keyの再配列が必要かも。
// $items = array_filter($items, 'myFilter');
// var_dump($items);
// echo "\n";
// $driver->executeScript("window.scrollTo(300, 300);");
$file = "サンプル_chrome.png";
$driver->takeScreenshot($file);
$driver->close();
