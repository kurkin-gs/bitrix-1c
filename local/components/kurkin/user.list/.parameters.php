<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

if(!CModule::IncludeModule("iblock"))
	return;

$fields = \Bitrix\Main\UserTable::GetMap();
$arProperty = array("" => GetMessage("KURKIN_USERS_COMP_NOT_SELECTED"));
foreach ($fields as $key => $field) {
    $arProperty[$key] = "[" . $key . "] ";
}

$arSorts = array("ASC" => GetMessage("KURKIN_USERS_COMP_ASC"), "DESC" => GetMessage("KURKIN_USERS_COMP_DESC"));
$arSortFields = array(
    "ID" => GetMessage("KURKIN_USERS_COMP_ID"),
    "LOGIN" => GetMessage("KURKIN_USERS_COMP_LOGIN"),
    "DATE_REGISTER" => GetMessage("KURKIN_USERS_COMP_DATE_REGISTER"),
    "LAST_LOGIN" => GetMessage("KURKIN_USERS_COMP_LAST_LOGIN")
);

$arComponentParameters = array(
    "GROUPS" => array(
    ),
    "PARAMETERS" => array(
        "AJAX_MODE" => array(),
        "ROWS_PER_PAGE" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("KURKIN_USERS_COMP_DESC_LIST_CONT"),
            "TYPE" => "STRING",
            "DEFAULT" => "20",
        ),
        "DISPLAY_FIELDS" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage('KURKIN_USERS_COMP_DISPLAY_FIELDS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arProperty,
            "ADDITIONAL_VALUES" => "N",
        ),
        "EXPORT_FIELDS" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage('KURKIN_USERS_COMP_EXPORT_FIELDS'),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arProperty,
            "ADDITIONAL_VALUES" => "N",
        ),
        "EXPORT_INTERVAL" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("KURKIN_USERS_COMP_EXPORT_INTERVAL"),
            "TYPE" => "STRING",
            "DEFAULT" => "30",
        ),
        "EXPORT_STEP_LIMIT" => array(
            "PARENT" => "BASE",
            "NAME" => GetMessage("KURKIN_USERS_COMP_EXPORT_STEP_LIMIT"),
            "TYPE" => "STRING",
            "DEFAULT" => "3000",
        ),
        "SORT_BY" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("KURKIN_USERS_COMP_IBORD1"),
            "TYPE" => "LIST",
            "DEFAULT" => "ACTIVE_FROM",
            "VALUES" => $arSortFields,
            "ADDITIONAL_VALUES" => "Y",
        ),
        "SORT_ORDER" => array(
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("KURKIN_USERS_COMP_IBBY1"),
            "TYPE" => "LIST",
            "DEFAULT" => "DESC",
            "VALUES" => $arSorts,
            "ADDITIONAL_VALUES" => "N",
        ),
        "CACHE_TIME" => array("DEFAULT" => 36000000),
    ),
);

CIBlockParameters::AddPagerSettings(
	$arComponentParameters,
	GetMessage("T_IBLOCK_DESC_PAGER_NEWS"), //$pager_title
	false, //$bDescNumbering
	false, //$bShowAllParam
	false, //$bBaseLink
	$arCurrentValues["PAGER_BASE_LINK_ENABLE"]==="Y" //$bBaseLinkEnabled
); 
