<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

$requiredModules = array('highloadblock');

foreach ($requiredModules as $requiredModule) {
    if (!CModule::IncludeModule($requiredModule)) {
	ShowError(GetMessage("F_NO_MODULE"));
	return 0;
    }
}

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

// hlblock info
//Подрубрики  - Начало
$hlblock_id = $arParams['SUB_BLOCK_ID'];
$hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();
$arResult['SUB_HL_BLOCK'] = $hlblock;
$entity = HL\HighloadBlockTable::compileEntity($hlblock);
$main_query = new Entity\Query($entity);
$main_query->setSelect(array('*'));
$main_query->setOrder(array('ID' => 'DESC'));
$result = $main_query->exec();
$result = new CDBResult($result);
$rowsSub = array();
$tableColumns = array();

while ($row = $result->Fetch()) {
    foreach ($row as $k => $v) {
	$tableColumns[$k] = true;

	$row[$k] = $v;
    }
    $row["COUNT"] = CIBlockElement::GetList(Array(), array("IBLOCK_ID" => IBLOCK_VACANCIES, "ACTIVE" => "Y", "ACTIVE_DATE" => "Y", $arParams["IBLOCK_PROPERTY"] => $row["UF_XML_ID"]), array());
    $rowsSub[$row[$arParams["PARENT_PROPERTY"]]][] = $row;
}
//Подрубрики  - Конец

$hlblock_id = $arParams['BLOCK_ID'];

if (empty($hlblock_id)) {
    ShowError(GetMessage('HLBLOCK_LIST_NO_ID'));
    return 0;
}

$hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();
$arResult['HL_BLOCK'] = $hlblock;

if (empty($hlblock)) {
    ShowError('404');
    return 0;
}

$entity = HL\HighloadBlockTable::compileEntity($hlblock);


// uf info
$fields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('HLBLOCK_' . $hlblock['ID'], 0, LANGUAGE_ID);

// pagination
$limit = array(
    'nPageSize' => $arParams['ROWS_PER_PAGE'],
    'iNumPage' => is_set($_GET['PAGEN_1']) ? $_GET['PAGEN_1'] : 1,
    'bShowAll' => true
);

// sort
$sort_id = 'ID';
$sort_type = 'DESC';

if (!empty($_GET['sort_id']) && (isset($fields[$_GET['sort_id']]))) {
    $sort_id = $_GET['sort_id'];
}

if (!empty($_GET['sort_type']) && in_array($_GET['sort_type'], array('ASC', 'DESC'), true)) {
    $sort_type = $_GET['sort_type'];
}

$sort = ($arParams["SORT"]) ? $arParams["SORT"] : array("UF_SORT" => "ASC");

// execute query

$main_query = new Entity\Query($entity);
$main_query->setSelect(array('*'));
$main_query->setFilter(array('!UF_SHOW_FILTER' => false));
$main_query->setOrder($sort);
$result = $main_query->exec();
$result = new CDBResult($result);

// build results
$rows = array();

$tableColumns = array();
while ($row = $result->Fetch()) {
    foreach ($row as $k => $v) {
	$row[$k] = $v;
    }

    $row['SUB'] = $rowsSub[$row['UF_XML_ID']];

    if (($row['UF_MAIN'] && $arParams['MAIN'] == 'Y') || $arParams['MAIN'] != 'Y')
	$rows[] = $row;
}


$arResult['rows'] = $rows;
$arResult['fields'] = $fields;
$arResult['tableColumns'] = $tableColumns;

$arResult['sort_id'] = $sort_id;
$arResult['sort_type'] = $sort_type;

$this->IncludeComponentTemplate();
