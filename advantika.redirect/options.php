<?php
namespace Advantika\Redirect;
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

Loc::loadMessages(__FILE__);

$siteId = $request->get('site_id');
if (!$siteId)
{
	$site = \CSite::GetList($by = 'sort', $order = 'asc', [
		'DEFAULT' => 'Y',
	])->Fetch();
	if (!$site)
	{
		$site = \CSite::GetList($by = 'sort', $order = 'asc', [
			'ACTIVE' => 'Y',
		])->Fetch();
	}
	if (!$site)
	{
		$site = \CSite::GetList($by = 'sort', $order = 'asc')->Fetch();
	}
}
else
{
	$site = \CSite::GetByID($siteId)->Fetch();
}
if (empty($site['LID']))
{
	\CAdminMessage::ShowMessage(
		Loc::getMessage('ADVANTIKA_REDIRECT_SITE_NOT_DEFINED')
	);
	return;
}

if (check_bitrix_sessid() && $request->isPost())
{

	if ($request->getPost('save') != '')
	{
		$data = $request->getPostList();

        //Получаем загруженый файл и определяем его местоположение
        $result_url = [];
        if(!empty($data['file_url_redirect'][0])){
            //$csv = \CFile::GetPath('2099');
            $csv = \CFile::GetPath($data['file_url_redirect'][0]);
            $sep = "\t";
            //Открываем файл и получаем данные из файла перебором в цикле
            $file = fopen($_SERVER['DOCUMENT_ROOT'].$csv, "r");
            while (($row_url = fgetcsv($file, 4000, $sep)) !== false) {
                $result_url[] = array_map("trim", $row_url);
            }
            fclose($file);
        }

        if ($request->get('advantika_action') == 'redirects')
		{
			Update($data, $site['LID'], $result_url);
           \CFile::Delete($data['file_url_redirect'][0]);
            if($data['Delit404list'] == 'Y'){
               unlink(OptionsFilename('urls404', $site['LID'], '.csv'));
            }
		}
		else
		{
			OptionsUpdate($data, $site['LID']);
            if($data['Delit404list'] == 'Y'){
                unlink(OptionsFilename('urls404', $site['LID'], '.csv'));
            }
		}
		\CAdminMessage::ShowNote(
			Loc::getMessage('ADVANTIKA_REDIRECT_SETTINGS_SAVED')
		);
	}
}
$currentOptions = Options($site['LID']);

$title = '[' . $site['LID'] . '] ' .  $site['NAME'];
if ($request->get('advantika_action') == 'redirects')
{
	$description = Loc::getMessage('ADVANTIKA_REDIRECT_TITLE_REDIRECTS');
}
else
{
	$description = Loc::getMessage('ADVANTIKA_REDIRECT_OPTIONS_TITLE');
}
$aTabs[] = array(
    'DIV' => 'edit1',
	'TAB' => $title,
	'TITLE' => $description
);
$aTabs[] = array(
    'DIV' => 'edit2',
    'TAB' => 'Список 404',
    'TITLE' => 'Список 404'
);
$APPLICATION->SetAdditionalCSS(SITE_DIR."/bitrix/modules/advantika.redirect/assets/css/style.css");
$tabControl = new \CAdminTabControl("tabControl", $aTabs);
$tabControl->begin();

?>

<form method="post" action="">
	<?= bitrix_sessid_post() ?>

	<?php $tabControl->beginNextTab() ?>

	<?php if ($request->get('advantika_action') == 'redirects') { ?>

	<tr>
		<td colspan="2">
            <?$APPLICATION->IncludeComponent("bitrix:main.file.input", "drag_n_drop",
                array(
                    "INPUT_NAME"=>"file_url_redirect",
                    "MULTIPLE"=>"Y",
                    "MODULE_ID"=>"main",
                    "MAX_FILE_SIZE"=>"",
                    "ALLOW_UPLOAD"=>"A",
                    "ALLOW_UPLOAD_EXT"=>"",
                    "INPUT_CAPTION" => "Добавить csv файл",
                    "INPUT_VALUE" => $_POST['file_url_redirect']
                ),
                false
            );?>
			<table width="100%" class="js-table-autoappendrows">
				<tbody>
                <tr class="wrapp_title">
                    <td>
                        <label class="title_lable border_left"><?=Loc::getMessage("ADVANTIKA_REDIRECT_URLS_FROM") ?></label>
                    </td>
                    <td>
                        <label class="title_lable"><?=Loc::getMessage("ADVANTIKA_REDIRECT_URLS_TO") ?></label>
                    </td>
                    <td>
                        <label class="title_lable"><?= Loc::getMessage("ADVANTIKA_REDIRECT_URLS_STATUS") ?></label>
                    </td>
                    <td>
                        <label class="title_lable border_right">Удалить?</label>
                    </td>
                </tr>
					<?php
					$i = -1;
					foreach (AppendValues(Select(true, $site['LID']), 5, ['', '', '']) as $url) {
						$i++;
					?>
						<tr data-idx="<?= $i ?>">
							<td>
								<input type="text" placeholder="<?=
										Loc::getMessage("ADVANTIKA_REDIRECT_URLS_FROM") ?>"
									name="redirect_urls[<?= $i ?>][0]"
									value="<?= htmlspecialcharsex($url[0]) ?>"
									style="width:96%;">
							</td>
							<td>
								<input type="text" placeholder="<?=
										Loc::getMessage("ADVANTIKA_REDIRECT_URLS_TO") ?>"
									name="redirect_urls[<?= $i ?>][1]"
									value="<?= htmlspecialcharsex($url[1]) ?>"
									style="width:96%;">
							</td>
							<td>
								<select name="redirect_urls[<?= $i ?>][2]"
										title="<?= Loc::getMessage("ADVANTIKA_REDIRECT_URLS_STATUS") ?>"
										style="width:96%;">
									<option value="301" <?= $url[2] == "301"? "selected" : "" ?>>301</option>
									<option value="302" <?= $url[2] == "302"? "selected" : "" ?>>302</option>
								</select>
							</td>
							<td class="text-align_center">
								<input name="redirect_urls[<?= $i ?>][3]" value="Y" type="checkbox"
									title="<?= Loc::getMessage("ADVANTIKA_REDIRECT_URLS_IS_PART_URL") ?>"
									<?= $url[3] == "Y"? "checked" : "" ?>>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>

		</td>
	</tr>

	<?php } else { ?>
        <tr class="heading ">
            <td class="adm-detail-content-cell-l adm-detail-title gray-section_down" colspan="2">
                <label>
                    <?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_TITLE_ALL") ?>
                </label>
            </td>
        </tr>
	<tr>

		<td class="adm-detail-content-cell-l" width="50%">
			<label>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_WWW_TITLE") ?>
			</label>
		</td>
<!--		<td class="adm-detail-content-cell-r" width="50%">-->
<!--			<input name="redirect_www" value="Y" type="checkbox"-->
<!--				--><?php //= $currentOptions["redirect_www"] == "Y"? "checked" : "" ?><!--
		</td>-->
        <td class="adm-detail-content-cell-r checkbox-wrapper" width="50%">
            <label>
                <input class="modern-radio" name="redirect_www" value="" type="radio"
                    <?= $currentOptions["redirect_www"] == ""? "checked" : "" ?>>
                <span></span>
                <?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_WWW_NO") ?>
            </label>
            <br>
            <label>
                <input class="modern-radio" name="redirect_www" value="to_www" type="radio"
                    <?= $currentOptions["redirect_www"] == "to_www"? "checked" : "" ?>>
                <span></span>
                <?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_WWW_TO") ?>
            </label>
            <br>
            <label>
                <input class="modern-radio" name="redirect_www" value="not_www" type="radio"
                    <?= $currentOptions["redirect_www"] == "not_www"? "checked" : "" ?>>
                <span></span>
                <?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_WWW_NOT") ?>
            </label>
        </td>
	</tr>

	<tr>
		<td class="adm-detail-content-cell-l" width="50%">
			<label>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_HTTPS_TITLE") ?>
			</label>
		</td>
		<td class="adm-detail-content-cell-r checkbox-wrapper" width="50%">
			<label>
				<input class="modern-radio" name="redirect_https" value="" type="radio"
					<?= $currentOptions["redirect_https"] == ""? "checked" : "" ?>>
                <span></span>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_HTTPS_NO") ?>
			</label>
			<br>
			<label>
				<input class="modern-radio" name="redirect_https" value="to_https" type="radio"
					<?= $currentOptions["redirect_https"] == "to_https"? "checked" : "" ?>>
                <span></span>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_HTTPS_HTTPS") ?>
			</label>
			<br>
			<label>
				<input class="modern-radio" name="redirect_https" value="to_http" type="radio"
					<?= $currentOptions["redirect_https"] == "to_http"? "checked" : "" ?>>
                <span></span>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_HTTPS_HTTP") ?>
			</label>
		</td>
	</tr>

	<tr>
		<td class="adm-detail-content-cell-l" width="50%">
			<label>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_SLASH_TITLE") ?>
			</label>
		</td>
		<td class="adm-detail-content-cell-r" width="50%">
			<input name="redirect_slash" value="Y" type="checkbox"
				<?= $currentOptions["redirect_slash"] == "Y"? "checked" : "" ?>>
		</td>
	</tr>

	<tr>
		<td class="adm-detail-content-cell-l" width="50%">
			<label>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_INDEX_TITLE") ?>
			</label>
		</td>
		<td class="adm-detail-content-cell-r" width="50%">
			<input name="redirect_index" value="Y" type="checkbox"
				<?= $currentOptions["redirect_index"] == "Y"? "checked" : "" ?>>
		</td>
	</tr>

	<tr>
		<td class="adm-detail-content-cell-l" width="50%">
			<label>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_MULTISLASH_TITLE") ?>
			</label>
		</td>
		<td class="adm-detail-content-cell-r" width="50%">
			<input name="redirect_multislash" value="Y" type="checkbox"
				<?= $currentOptions["redirect_multislash"] == "Y"? "checked" : "" ?>>
		</td>
	</tr>
    <tr>
        <td class="adm-detail-content-cell-l" width="50%">
            <label>
                <?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_LOWER_TITLE") ?>
            </label>
        </td>
        <td class="adm-detail-content-cell-r" width="50%">
            <input name="redirect_lower" value="Y" type="checkbox"
                <?= $currentOptions["redirect_lower"] == "Y"? "checked" : "" ?>>
        </td>
    </tr>

	<tr>
		<td class="adm-detail-content-cell-l" width="50%">
			<label>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_IGNORE_QUERY") ?>
			</label>
		</td>
		<td class="adm-detail-content-cell-r" width="50%">
			<input name="ignore_query" value="Y" type="checkbox"
				<?= $currentOptions["ignore_query"] == "Y"? "checked" : "" ?>>
		</td>
	</tr>

	<tr>
		<td class="adm-detail-content-cell-l" width="50%">
			<label>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_FROM_404") ?>
			</label>
		</td>
		<td class="adm-detail-content-cell-r" width="50%">
			<input name="redirect_from_404" value="Y" type="checkbox"
				<?= $currentOptions["redirect_from_404"] == "Y"? "checked" : "" ?>>
		</td>
	</tr>

	<tr>
		<td class="adm-detail-content-cell-l" width="50%">
			<label>
				<?= Loc::getMessage("ADVANTIKA_REDIRECT_URLS_TITLE") ?>
			</label>
		</td>
		<td class="adm-detail-content-cell-r" width="50%">
			<input name="use_redirect_urls" value="Y" type="checkbox"
				<?= $currentOptions["use_redirect_urls"] == "Y"? "checked" : "" ?>>
			<?= str_replace($_SERVER['DOCUMENT_ROOT'], '', OptionsFilename('urls', $site['LID'], '.csv')) ?>
		</td>
	</tr>
    <tr class="heading">
        <td class="adm-detail-content-cell-l adm-detail-title gray-section" colspan="2">
            <label>
                <?= Loc::getMessage("ADVANTIKA_REDIRECT_OPTIONS_TITLE_CATALOG") ?>
            </label>
        </td>
    </tr>
        <tr>
            <td class="adm-detail-content-cell-l" width="50%">
                <label>
                    <?= Loc::getMessage("ADVANTIKA_REDIRECT_FROM_CATALOG_NAME") ?>
                </label>
            </td>
            <td class="adm-detail-content-cell-r" width="50%">
                <select multiple  name="catalog_id[]">
                    <option value="">Нет значений</option>
                    <?
                    if (\CModule::IncludeModule("iblock"))
                    {
                        // получаем список инфоблоков
                        $arBlocks = [];
                        $rsBlocks = \CIBlock::GetList(['SORT' => 'ASC'], ['SITE_ID' => $site['LID']]);
                        while ($arBlock = $rsBlocks->Fetch())
                        {
                            $arBlocks[$arBlock['ID']] = '[' . $arBlock['ID'] . '] ' . $arBlock['NAME'];
                        }
                    }
                    foreach ($arBlocks as $i => $option):?>
                        <option value="<?=$i?>"
                            <? foreach ($currentOptions['catalog_id'] as $id){
                            if($id == $i){
                                echo 'selected';
                            }else{
                                echo '';
                            }
                        }?>
                        ><?=$option?></option>
                    <?endforeach;?>
                </select>
            </td>
        </tr>
        <tr class="internal">
            <td class="align-center gray-section_options" colspan="2">
                <label>
                    <?= Loc::getMessage("ADVANTIKA_REDIRECT_FROM_CATALOG_SECTION") ?>
                </label>
            </td>
        </tr>
<?

?>

	<?php } ?>



<?$tabControl->BeginNextTab();
?>

    <tr>
        <td colspan="2">
            <table width="100%" class="js-table-autoappendrows">
                <tbody>
                <tr class="adm-list-table-header">
                    <td class="adm-list-table-cell adm-list-table-cell-sort" title="URL 404" width="70%">
                        <div class="adm-list-table-cell-inner">URL 404</div>
                    </td>
                    <td class="adm-list-table-cell adm-list-table-cell-sort" title="Время захода на ссылку">
                        <div class="adm-list-table-cell-inner">Время захода на ссылку</div>
                    </td>
                </tr>
                <?php
                $i = -1;
                foreach (AppendValues(Select(false, $site['LID']), 0, ['', '', '']) as $url) {

                    $i++;
                    ?>
                    <tr data-idx="<?= $i ?>" class="adm-list-table-row adm-list-row-active">
                        <td class="adm-list-table-cell">
                            <a name="List_404[<?=$i?>][0]" href="<?= htmlspecialcharsex($url[0]) ?>"><?= htmlspecialcharsex($url[0]) ?></a>
                        </td>
                        <td class="adm-list-table-cell">
                            <span name="List_404[<?=$i?>][1]"><?= htmlspecialcharsex($url[1]) ?></span>
                        </td>
                    </tr>
                <?php } ?>
                <tr colspan="2">
                    <td class="adm-detail-content-cell-l" width="30%">
                    </td>
                    <td class="adm-detail-content-cell-l" width="30%">
                        <label>
                            Сбросить данные по списку 404
                        </label>
                        <input name="Delit404list" value="Y" type="checkbox">
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    <?php $tabControl->buttons(); ?>

    <input class="adm-btn-save" type="submit" name="save"
           value="<?= Loc::getMessage("ADVANTIKA_REDIRECT_SAVE_SETTINGS") ?>">
<?php
$tabControl->end();?>
</form>

<script>

BX.ready(function () {
	"use strict";
	// autoappend rows
	function makeAutoAppend($table) {
		function bindEvents($row) {
			for (let $input of $row.querySelectorAll('input[type="text"]')) {
				$input.addEventListener("change", function (event) {
					let $tr = event.target.closest("tr");
					let $trLast = $table.rows[$table.rows.length - 1];
					if ($tr != $trLast) {
						return;
					}
					$table.insertRow(-1);
					$trLast = $table.rows[$table.rows.length - 1];
					$trLast.innerHTML = $tr.innerHTML;
					let idx = parseInt($tr.getAttribute("data-idx")) + 1;
					$trLast.setAttribute("data-idx", idx);
					for (let $input of $trLast.querySelectorAll("input,select")) {
						let name = $input.getAttribute("name");
						if (name) {
							$input.setAttribute("name", name.replace(/([a-zA-Z0-9])\[\d+\]/, "$1[" + idx + "]"));
						}
					}
					bindEvents($trLast);
				});
			}
		}
		for (let $row of document.querySelectorAll(".js-table-autoappendrows tr")) {
			bindEvents($row);
		}
	}
	for (let $table of document.querySelectorAll(".js-table-autoappendrows")) {
		makeAutoAppend($table);
	}
});

</script>
