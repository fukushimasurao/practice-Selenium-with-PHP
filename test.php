<?php
require './vendor/autoload.php';
 
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
 
$word = 'rakuten';

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

// $file = "サンプル_chrome.png";
// $driver->takeScreenshot($file);
#root > div > div.ant-row.c10-Cg > div.ant-col-24 > div > div.ant-col-20.ant-col-push-4.c1z9Ut > div.cUQuRr > div > div.c3hhWj > div > div
// class=" c1DXz4"
// data-spm-anchor-id="a2o4l.searchlist.0.i2.561a574c2XCEHx"

// $testId = $driver->findElement(WebDriverBy::className('c13VH6'));

$products = [];
$productName = $driver->findElements(WebDriverBy::className('c16H9d'));
$prices = $driver->findElements(WebDriverBy::className('c13VH6'));

if(count($productName) >= 10) {
    foreach ($productName as $k => $v) {
        if($k === 10) {
        break;
    }
        $price = $v->getText();
        echo "\n";
        echo "[" . $k+1 . "]" . $price;
        echo "\n";
    }
} else {
        $price = $v->getText();
        echo "\n";
        echo "[" . $k+1 . "]" . $price;
        echo "\n";
}



// $text = $testId->getText();
// echo "\n";
// echo $text."\n";

// $a = $driver->findElemenet(WebDriverBy::className("lzd-logo-content"));
// foreach ($a as $v) {
//     echo  $v; 
// }

$driver->close();