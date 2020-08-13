<?php

namespace App\Service;

require 'vendor/autoload.php';

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverDimension;


$a = new Lazada;

var_dump($a->lazadaSearch('japan'));

// test

class Lazada
{
    /**
     * @param string $word lazadaで検索したいしたい文言
     * @return array $items スクレイピングした商品情報を配列で返す。
     */
    public static function lazadaSearch($word)
    {
        // chrome利用用意
        $options = new ChromeOptions();
        $options->addArguments(['--headless']);
        $options->addArguments(["window-size=1024,2048"]);

        $host = 'http://localhost:5555/wd/hub';
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = RemoteWebDriver::create($host, $capabilities);
        $driver->get('https://www.lazada.com.ph/');
        $browserLogs = $driver->manage()->getLog('browser');
        $element = $driver->findElement(WebDriverBy::name('q'));
        $element->sendKeys($word);
        $element->submit();

        // 商品画像を全部表示するため、画面を少し右に移動
        $driver->executeScript("window.scrollTo(300, 0);");

        $title = "$word - Buy $word at Best Price in Philippines | www.lazada.com.ph";
        $driver->wait(15)->until(
            WebDriverExpectedCondition::titleIs($title)
        );
        // if ($driver->getTitle() !== "$title") {
        //     throw new Exception('fail');
        // }

        // lazada検査画面から、それぞれ情報を取得
        $itemUrls = $driver->findElements(WebDriverBy::cssSelector('div.c2iYAv > div.cRjKsc > a')); //->text
        $photos = $driver->findElements(WebDriverBy::cssSelector('.c5TXIP .c2iYAv .cRjKsc .c1ZEkM'));
        $productNames = $driver->findElements(WebDriverBy::cssSelector('.c5TXIP .c3KeDq .c16H9d')); //->text

        $items = [];
        // 商品あるかチェック
        // if (count($photos) < 0) {
        //     throw new Exception('no item.');
        // }

        foreach ($itemUrls as $k => $v) {
            if ($k === 10) {
                break;
            }
            $items[$k]['scrape_url'] = $v->getAttribute('href');
        }
        foreach ($photos as $k => $v) {
            if ($k === 10) {
                break;
            }
            $items[$k]['scrape_img'] = $v->getAttribute('src');
        }
        foreach ($productNames as $k => $v) {
            if ($k === 10) {
                break;
            }
            $items[$k]['scrape_title'] = $v->getText();
        }

        /*
        * //このコメントアウト外せば、どこをスクレイピングしているかとスクリーンショットで確認できる
        * $file = "サンプル_chrome.png";
        * $driver->takeScreenshot($file);
        */

        print_r($items);
        $driver->close();
        return $items;
    }
}
