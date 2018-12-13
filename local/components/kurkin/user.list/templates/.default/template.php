<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
?>
<div class="users-list">
    <? if ($arParams["DISPLAY_TOP_PAGER"]) { ?>
        <?= $arResult["NAV_STRING"] ?>
    <? } ?>
    <div class="forms-wrapper">
        <form action="" method="POST">
            <input type="hidden" name="save_csv" value="Y" />
            <button type="button">Экспорт в CSV</button>
        </form>
        <form action="" method="POST">
            <input type="hidden" name="save_xml" value="Y" />
            <button type="button">Экспорт в XML</button>
        </form>
        <div style="clear:both"></div>
    </div>
    <div class="table-wrapper">
        <table style="width:100%">
            <thead>
                <tr>
                    <? foreach ($arParams["DISPLAY_FIELDS"] as $key => $field) { ?>
                        <th><?= $field; ?></th>
                        <?
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <? foreach ($arResult["ITEMS"] as $arItem) { ?>
                    <tr>
                        <? foreach ($arParams["DISPLAY_FIELDS"] as $key => $field) { ?>
                            <td><?= $arItem[$field]; ?></td>
                        <? } ?>
                    </tr>
                <? } ?>
            </tbody>
        </table>
    </div>

    <? if ($arParams["DISPLAY_BOTTOM_PAGER"]) { ?>
        <?= $arResult["NAV_STRING"] ?>
    <? } ?>
</div>