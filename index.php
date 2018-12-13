<?

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("");
?>
<?
$APPLICATION->IncludeComponent(
        "kurkin:user.list", ".default", array(
    "AJAX_MODE" => "Y",
    "ROWS_PER_PAGE" => "10",
    "DISPLAY_FIELDS" => array(
    ),
    "EXPORT_FIELDS" => array(
    ),
    "EXPORT_INTERVAL" => "10",
    "EXPORT_STEP_LIMIT" => "3000",
    "COMPONENT_TEMPLATE" => ".default",
    "SORT_BY" => "ID",
    "SORT_ORDER" => "ASC",
    "AJAX_OPTION_JUMP" => "N",
    "AJAX_OPTION_STYLE" => "Y",
    "AJAX_OPTION_HISTORY" => "N",
    "AJAX_OPTION_ADDITIONAL" => "undefined",
    "CACHE_TYPE" => "Y",
    "CACHE_TIME" => "36000000",
    "CACHE_FILTER" => "N",
    "CACHE_GROUPS" => "Y",
    "PAGER_TEMPLATE" => "arrows",
    "PAGER_TITLE" => "",
    "DISPLAY_TOP_PAGER" => "N",
    "DISPLAY_BOTTOM_PAGER" => "Y",
    "PAGER_SHOW_ALWAYS" => "N"
        ), false
);
?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>