<?php

namespace Advantika\Redirect;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\IO\Directory;
use Bitrix\Main;

const ID = 'advantika.redirect';
const APP = __DIR__ . '/';
const LIB = APP . 'lib/';

define(
	__NAMESPACE__ . '\\CONFIG_DIR',
	$_SERVER['DOCUMENT_ROOT'] . '/local/config'
);
const CONFIG = CONFIG_DIR . '/.' . ID . '.';

const IGNORED_TEMPLATES = [
	'bitrix24' => 1,
	'desktop_app' => 1,
	'learning' => 1,
	'login' => 1,
	'mail_user' => 1,
	'mobile_app' => 1,
	'pub' => 1,
];

require LIB . 'encoding/include.php';

function AppendValues($data, $n, $v)
{
	yield from $data;
	for ($i = 0; $i < $n; ++$i)
	{
		yield  $v;
	}
}

function Options($siteId)
{
	$fname = OptionsFilename('options', $siteId);
	return is_readable($fname) ?
		include $fname : [
			'redirect_www' => 'Y',
			'redirect_https' => '',
			'redirect_slash' => 'Y',
			'redirect_index' => 'Y',
			'use_redirect_urls' => 'N',
			'ignore_query' => 'Y',
			'redirect_from_404' => 'N',
			'catalog_id' => '',
			'redirect_lower' => 'N',
		];
}

function OptionsUpdate($data, $siteId)
{

    $fname = OptionsFilename('options', $siteId);
	if (!is_dir(CONFIG_DIR))
	{
		Directory::createDirectory(CONFIG_DIR);
	}
	\Encoding\PhpArray\Write($fname, [
		'redirect_www' => $data['redirect_www'],
		'redirect_https' => $data['redirect_https'],
		'redirect_slash' => $data['redirect_slash'],
		'redirect_index' => $data['redirect_index'],
		'redirect_multislash' => $data['redirect_multislash'],
		'use_redirect_urls' => $data['use_redirect_urls'],
		'ignore_query' => $data['ignore_query'],
		'redirect_from_404' => $data['redirect_from_404'],
        'catalog_id' => $data['catalog_id'],
        'redirect_lower' => $data['redirect_lower'],
	]);
}

function OptionsFilename($group, $siteId, $ext = '.php')
{
	return CONFIG . $group . '.' . $siteId . $ext;
}

function Select($fromCsv, $siteId)
{
	if ($fromCsv)
	{
		return \Encoding\Csv\Read(OptionsFilename('urls', $siteId, '.csv'));
	}else{
        return \Encoding\Csv\Read(OptionsFilename('urls404', $siteId, '.csv'));
    }
	$fname = OptionsFilename('urls', $siteId);
	return is_readable($fname) ? include $fname : [];
}

function Update($data, $siteId, $result_url)
{
	$urls = [];
	$urlsMap = [];
    $urlList = [];

    if(!empty($result_url)){
        //Собираем массив по индексам ссылко, чтобы потом произвести проверку на наличие дубликатов.
        foreach ($result_url as $i => $key){
            $arrayMapCsvList[$key[0]]= [$key[0],$key[1],$key[2]];
        }
        foreach ($data['redirect_urls'] as $i => $key){
            $key_one = trim($key[0]);
            $key_two = trim($key[1]);
            $key_free = trim($key[2]);
            if ('' != $key_one && '' != $key_two){
                $arrayMapList[$key_one] = [$key_one,$key_two,$key_free];
            }
        }
        // Проверяем на дубликаты ссылок.
        if(count($arrayMapCsvList) >= count($arrayMapList)){
            //Собираем массив по исходным индексам если загружаемый список больше чем тот который уже имеется на сайте.
            foreach ($arrayMapCsvList as $i => $key){
                if($i != $arrayMapList[$i][0]){
                    $urlList[] = $arrayMapCsvList[$i];
                    $urlsMapCsvList[$arrayMapCsvList[$i][0]] = [$arrayMapCsvList[$i][1], trim($arrayMapCsvList[$i][2]), trim($arrayMapCsvList[$i][3])];
                }
            }
        }else{
            // Если ссылок больше в уже загруженном списке, то проверяем на совпадение одинаковых ссылок и записываем их в массив.
            foreach ($arrayMapList as $i => $key){
                if("" != $arrayMapCsvList[$i]){
                    $urlListCsv[$arrayMapCsvList[$i][0]] = $arrayMapCsvList[$i];
                }
            }
            // После сбора данных и загузки индексов в массив, проверяем на различие между ними.
            foreach ($arrayMapCsvList as $index => $keyCsv){
                if ($index != $urlListCsv[$index][0]){
                    $urlList[] = $arrayMapCsvList[$index];
                    $urlsMapCsvList[$arrayMapCsvList[$index][0]] = [$arrayMapCsvList[$index][1], trim($arrayMapCsvList[$index][2]), trim($arrayMapCsvList[$index][3])];
                }
            }
        }
    }
    // Собираем данные по ссылкам загруженные через список в модуле.
    for ($i=0;$i <= count($data['redirect_urls']); $i++){
        $from = trim($data['redirect_urls'][$i][0]);
        $to = trim($data['redirect_urls'][$i][1]);

        if ('' != $from && '' != $to)
        {
            $urls[] = $data['redirect_urls'][$i];
            $urlsMap[$from] = [$to, trim($data['redirect_urls'][$i][2]), trim($data['redirect_urls'][$i][3])];
        }
    }
    //Производим слияние загруженных ссылок с файла и ссылоки имеющиеся на сайте
    if(!empty($urlList) && !empty($urlsMapCsvList)){
        $urlNewList = array_merge($urls,$urlList);
        $urlsMapNew = array_merge($urlsMap,$urlsMapCsvList);
    }
	if (!is_dir(CONFIG_DIR))
	{
		Directory::createDirectory(CONFIG_DIR);
	}
    //Производим запись информации в файл.
	\Encoding\Csv\Write(OptionsFilename('urls', $siteId, '.csv'), !empty($urlNewList)?$urlNewList:$urls);
	\Encoding\PhpArray\Write(OptionsFilename('urls', $siteId), !empty($urlsMapNew)?$urlsMapNew:$urlsMap);
}
function Write404List(){
    if (defined('ERROR_404')){
        $url404 = $_SERVER['HTTP_X_FORWARDED_PROTO'].'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        $time = date('d.m.Y H:i:s');
        $data404[] = [$url404,$time];
        $listOld404 = \Encoding\Csv\Read(OptionsFilename('urls404', SITE_ID, '.csv'));
        foreach ($listOld404 as $i=> $url){
            if($url[0] != $data404[0][0]){
                $urlOld404New[] = $url;
            }
        }
        if(empty($listOld404)){
            \Encoding\Csv\Write(OptionsFilename('urls404', SITE_ID, '.csv'), $data404);
        }
        if(!empty($urlOld404New)){
            $urlNewList = array_merge($urlOld404New,$data404);
            \Encoding\Csv\Write(OptionsFilename('urls404', SITE_ID, '.csv'), $urlNewList);
        }
    }
}

//FIX for php 8.0
function parse_url($uri, $component = -1)
{
	// NOTE $uri without scheme and host
	$url = 'https://localhost' . $uri;

	$result = \parse_url($url, $component);

	return $result;
}


function HandlerRedirectUrl()
{
	if (('GET' != $_SERVER['REQUEST_METHOD']) && ('HEAD' != $_SERVER['REQUEST_METHOD']))
	{
		//NOTE redirect only for GET and HEAD
		return;
	}
	if (defined('SITE_TEMPLATE_ID') && isset(IGNORED_TEMPLATES[SITE_TEMPLATE_ID]))
	{
		//NOTE ignore some bitrix24 templates
		return;
	}
	if (('cli' == php_sapi_name())
			|| defined('BX_CRONTAB')
			|| \CSite::InDir('/bitrix/'))
	{
		//NOTE ignore redirect for scripts from /bitrix/, cli scripts and cron scripts
		return;
	}

	$currentOptions = Options(SITE_ID);
	$host = $_SERVER['SERVER_NAME'];
	$protocol = (!empty($_SERVER['HTTPS'])
		&& ('off' != $_SERVER['HTTPS'])) ? 'https' : 'http';
	$port = (!empty($_SERVER['SERVER_PORT'])
				&& ('80' != $_SERVER['SERVER_PORT'])
				&& ('443' != $_SERVER['SERVER_PORT'])) ?
			(':' . $_SERVER['SERVER_PORT']) : '';

	$currentUri = $_SERVER['REQUEST_URI'];
	$url = null;
	$isAbsoluteUrl = false;

	if (('not_www' == $currentOptions['redirect_www'])
			&& ('www.' == substr($_SERVER['SERVER_NAME'], 0, 4)))
	{
        $host = substr($_SERVER['SERVER_NAME'], 4);
		$url = $currentUri;
	}

    if (('to_www' == $currentOptions['redirect_www'])
        && ('www.' != substr($_SERVER['SERVER_NAME'], 0, 4)))
    {
        $host = 'www.'.substr($_SERVER['SERVER_NAME'], 0);
        $url = $currentUri;
    }

	$toProtocol = $currentOptions['redirect_https'];
	if (('to_https' == $toProtocol) && ('http' == $protocol))
	{
		$protocol = 'https';
		$url = $currentUri;
	}
	elseif (('to_http' == $toProtocol) && ('https' == $protocol))
	{
		$protocol = 'http';
		$url = $currentUri;
	}

	if (('Y' == $currentOptions['redirect_index'])
			|| ('Y' == $currentOptions['redirect_slash'])
			|| ('Y' == $currentOptions['redirect_multislash']))
	{
		$changed = false;
		$u = parse_url($currentUri);
		if ('Y' == $currentOptions['redirect_index'])
		{
			$tmp = rtrim($u['path'], '/');
			if ('index.php' == basename($tmp))
			{
				$dname = dirname($tmp);
				$u['path'] = (DIRECTORY_SEPARATOR != $dname ? $dname : '') . '/';
				$changed = true;
			}
		}
		if ('Y' == $currentOptions['redirect_slash'])
		{
			$tmp = basename(rtrim($u['path'], '/'));
			// add slash to url
			if (('/' != substr($u['path'], -1, 1))
					&& ('.php' != substr($tmp, -4))
					&& ('.htm' != substr($tmp, -4))
					&& ('.html' != substr($tmp, -5)))
			{
				$u['path'] .= '/';
				$changed = true;
			}
		}
		if ('Y' == $currentOptions['redirect_multislash'])
		{
			if (false !== strpos($u['path'], '//'))
			{
				$u['path'] = preg_replace('{/+}s', '/', $u['path']);
				$changed = true;
			}
		}
		if ($changed)
		{
			$url = $u['path'];
			if (!empty($u['query']))
			{
				$url .= '?' . $u['query'];
			}
		}
	}

	$status = '';
	if ('Y' == $currentOptions['use_redirect_urls'])
	{
		if ('Y' == $currentOptions['ignore_query'])
		{
			$currentUri = parse_url($currentUri, PHP_URL_PATH);
		}
		$redirects = Select(false, SITE_ID);

		if (isset($redirects[$currentUri]))
		{
			list($url, $status) = $redirects[$currentUri];
			if ('http' == substr($url, 0, 4))
			{
				$isAbsoluteUrl = true;
			}
		}
		else
		{
			// find part url
			foreach ($redirects as $fromUri => $v)
			{
				list($toUri, $status, $partUrl) = $v;
				if ('Y' != $partUrl)
				{
					continue;
				}
				$reFromUri = '{' . str_replace("\*\*\*", '(.+?)', preg_quote($fromUri)) . '}s';
				if (preg_match($reFromUri, $currentUri, $m))
				{
					$tmp = [];
					foreach ($m as $matchIdx => $matchValue)
					{
						if ($matchIdx > 0)
						{
							$tmp['{' . $matchIdx . '}'] = $matchValue;
						}
					}
					$url = str_replace(array_keys($tmp), array_values($tmp), $toUri);
					break;
				}
			}
		}
	}
	$status = '302' == $status ?
		'302 Found' : '301 Moved Permanently';

	if (!empty($url))
	{
		if ($isAbsoluteUrl)
		{
			LocalRedirect($url, true, $status);
		}
		else
		{
			LocalRedirect($protocol . '://' . $host . $port . $url, true, $status);
		}
		exit;
	}

}
function RedirectCatalog(){
    $currentOptions = Options(SITE_ID);
//Проверяем на выбранный инфоблок
    if(!empty($currentOptions['catalog_id'])){
//Подключаем библиотеку. Вытягиваем символьный код инфоблока. Проверяем на наличие 404, если есть то выполняется редирект.
        if (\CModule::IncludeModule("iblock")){

            foreach ($currentOptions['catalog_id'] as $id){
                $resSection = \CIBlock::GetByID($id);
                if($ar_res = $resSection->GetNext()) {
                    $arResult = $ar_res;
                }

                $simbol_cod_infoblock = explode('#',$arResult['LIST_PAGE_URL'])[2];

                if (defined('ERROR_404')) {

                    $currentUrl = explode('?', $_SERVER['REQUEST_URI'])[0];
                    if (strpos($currentUrl, $simbol_cod_infoblock) !== false) {
                        $path = parse_url($currentUrl, PHP_URL_PATH);
                        $symbolCode = array_pop(explode("/", trim($path, "/")));
                        //Проверяем существует ли товар с таким кодом и если да отправляет на правельный адрес
                        if(is_numeric($symbolCode)){
                            checking_existence_product_id($symbolCode);
                        }else{
                            checking_existence_product_code($symbolCode);
                        }

                        //Проверяем существует ли раздел с таким кодом и если да отправляет на правельный адрес
                        checking_existence_section_code($symbolCode);
                    }
                }
            }
        }
    }
}

function OnEpilog()
{
        if (!defined('ERROR_404') || ERROR_404 != 'Y')
        {
            return;
        }
        $options = Options(SITE_ID);
        if ('Y' != $options['redirect_from_404'])
        {
            return;
        }

        global $APPLICATION;
        // get parent level url
        $originalUri = $uri = parse_url($APPLICATION->GetCurPage(false), PHP_URL_PATH);
        $segments = explode('/', trim($uri, '/'));
        array_pop($segments);
        if (count($segments) > 0)
        {
            $uri = '/' . implode('/', $segments) . '/';
        }
        else
        {
            $uri = '/';
        }
        if ($originalUri != $uri)
        {
            // redirect
            LocalRedirect($uri, false, '301 Moved Permanently');
            exit;
        }
}
function checking_existence_product_id($symbolCode)
{
    $currentOptions = Options(SITE_ID);
    if (\CModule::IncludeModule("iblock")) {
        $arFilter = array(
            'IBLOCK_ID' => $currentOptions['catalog_id'],
            'ID' => $symbolCode
        );

        $arSelect = array('CODE', 'DETAIL_PAGE_URL');
        $rsElement = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);

        if ($arElement = $rsElement->GetNext()) {

            $detailPageUrl = $arElement['DETAIL_PAGE_URL'];
            if($detailPageUrl){
                LocalRedirect($detailPageUrl, false, "301 Moved Permanently");
                exit;
            }
        }
    }
}
function checking_existence_product_code($symbolCode)
{
    $currentOptions = Options(SITE_ID);
    if (\CModule::IncludeModule("iblock")) {
        $arFilter = array(
            'IBLOCK_ID' => $currentOptions['catalog_id'],
            'CODE' => $symbolCode
        );

        $arSelect = array('ID', 'DETAIL_PAGE_URL');
        $rsElement = \CIBlockElement::GetList(array(), $arFilter, false, false, $arSelect);

        if ($arElement = $rsElement->GetNext()) {

            $detailPageUrl = $arElement['DETAIL_PAGE_URL'];
            LocalRedirect($detailPageUrl, false, "301 Moved Permanently");
            exit;
        }
    }
}
function checking_existence_section_code($symbolCode)
{
    $currentOptions = Options(SITE_ID);
    if (\CModule::IncludeModule("iblock")) {
        $arFilter = array('IBLOCK_ID' => $currentOptions['catalog_id'], 'GLOBAL_ACTIVE' => 'Y', 'ACTIVE' => 'Y');
        $dataSection = \CIBlockSection::GetList(array(), $arFilter, true, array('CODE', 'NAME'));
        while ($dataItem = $dataSection->GetNext()) {
            $data[] = $dataItem;
        }
        foreach ($data as $item) {
            if ($item['CODE'] === $symbolCode) {
                $sectionName = $item['NAME'];
                break;
            }
        }

        $sectionName = null;
        foreach ($data as $item) {
            if ($item['CODE'] === $symbolCode) {
                $sectionName = $item['NAME'];
                break;
            }
        }

        if ($sectionName) {
            $sectionFilter = [
                'IBLOCK_ID' => $currentOptions['catalog_id'],
                '=NAME' => $sectionName,
            ];
            $section = \CIBlockSection::GetList([], $sectionFilter, false, ['ID', 'SECTION_PAGE_URL'])->Fetch();
            if ($section) {
                $res = \CIBlockSection::GetByID($section['ID']);
                if ($ar_res = $res->GetNext()) {
                    LocalRedirect($ar_res['SECTION_PAGE_URL'], false, "301 Moved Permanently");
                    exit;
                }
            }
        }
    }
}

function toLower() {
    $currentOptions = Options(SITE_ID);
    if($currentOptions['redirect_lower'] == 'Y'){
        // Получаем запрашиваемый URL
        $url = $_SERVER['REQUEST_URI'];
        $params = $_SERVER['QUERY_STRING'];
        // Если URL содержат имена файлов или имена файлов зависит от конкретики
        if ( preg_match('/[\.]/', $url) ) {
            return;
        }
        // Если URL содержит заглавную букву
        if ( preg_match('/[A-Z]/', $url) ) {
            // Преобразование URL в нижний регистр
            $lc_url = empty($params)
                ? strtolower($url)
                : strtolower(substr($url, 0, strrpos($url, '?'))).'?'.$params;
            // если URL был изменен, перенаправлять
            if ($lc_url !== $url) {
                // 301 redirect на новый URL нижнего регистра
                LocalRedirect($lc_url, true, "301 Moved Permanently");
                exit();

            }
        }
    }
}

function init()
{
	Loc::loadMessages(__FILE__);

	AddEventHandler(
		'main',
		'OnBeforeProlog',
		__NAMESPACE__ . '\\HandlerRedirectUrl'
	);
    AddEventHandler(
        'main',
        'OnEpilog',
        __NAMESPACE__ . '\\Write404List'
    );
    AddEventHandler(
        'main',
        'OnEpilog',
        __NAMESPACE__ . '\\RedirectCatalog'
    );
	AddEventHandler(
		'main',
		'OnEpilog',
		__NAMESPACE__ . '\\OnEpilog'
	);
    AddEventHandler(
        'main',
        'OnBeforeProlog',
        __NAMESPACE__ . '\\toLower'
    );
	AddEventHandler('main', 'OnAdminListDisplay', function (&$list) {
		if ($list->table_id != 'tbl_site')
		{
			return;
		}

		\CJSCore::init('sidepanel');
		$urlSettings = '/bitrix/admin/settings.php?lang=ru&mid='
			. ID . '&IFRAME=Y';
		foreach ($list->aRows as &$row)
		{
			$url = $urlSettings . '&advantika_action=settings&site_id='
				. $row->arRes['LID'];
			$row->aActions[] = [
				'TEXT' => Loc::getMessage('ADVANTIKA_REDIRECT_MODULE_NAME')
					. Loc::getMessage('ADVANTIKA_REDIRECT_MENU_ITEM_SETTINGS'),
				'ACTION' => 'BX.SidePanel? BX.SidePanel.Instance.open("' . $url . '") : (location.href="' . $url . '");',
			];
			$url = $urlSettings . '&advantika_action=redirects&site_id='
				. $row->arRes['LID'];
			$row->aActions[] = [
				'TEXT' => Loc::getMessage('ADVANTIKA_REDIRECT_MODULE_NAME')
					. Loc::getMessage('ADVANTIKA_REDIRECT_MENU_ITEM_REDIRECTS'),
				'ACTION' => 'BX.SidePanel? BX.SidePanel.Instance.open("' . $url . '") : (location.href="' . $url . '");',
			];
		}
	});
}

init();
