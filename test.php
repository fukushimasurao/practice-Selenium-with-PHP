<?php
require './vendor/autoload.php';
 
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
 
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
$driver->wait(15)->until(
    WebDriverExpectedCondition::titleIs($title)
);

if ($driver->getTitle() !== "$title") {
    throw new Exception('fail');
}

$photos = $driver->findElements(WebDriverBy::cssSelector('.c5TXIP .c2iYAv .cRjKsc .c1ZEkM')); //写真！
// $productName = $driver->findElements(WebDriverBy::className('c16H9d'));

foreach ($photos as $k => $v) {
    if($k === 10) {
        break;
    }
    $name = $v->getAttribute('src');
    echo($name);
    echo "\n";
}

// if(count($productName) >= 10) {
//     foreach ($productName as $k => $v) {
//         if($k === 10) {
//             break;
//         }
//         $products[$k]['item'] = $v->getText();
//         // $name = $v->getText();
//         // $products[$k] = $name;
//         // echo "\n";
//         // echo "[" . $k . "]" . $name;
//         // echo "\n";
//     }
//     foreach ($prices as $k => $v) {
//         if($k === 10) {
//             break;
//         }
//         $products[$k]['price'] = $v->getText();
//         // $name = $v->getText();
//         // $products[$k] = $name;
//         // echo "\n";
//         // echo "[" . $k . "]" . $name;
//         // echo "\n";
//     }
// } else {
//     foreach ($productName as $k => $v) {
//         $name = $v->getText();
//         $products[$k]['item'] = $name;
//     }
//     foreach ($prices as $k => $v) {
//         $name = $v->getText();
//         $products[$k]['price'] = $name;
//     }
// }
// print_r($products);
// $text = $testId->getText();
// echo "\n";
// echo $text."\n";

// $a = $driver->findElemenet(WebDriverBy::className("lzd-logo-content"));
// foreach ($a as $v) {
//     echo  $v; 
// }

$driver->close();