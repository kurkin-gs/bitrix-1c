<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
IncludeTemplateLangFile(__FILE__);
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <?
        $APPLICATION->ShowHead();
        ?>
        <title><? $APPLICATION->ShowTitle() ?></title>
    </head>

    <body>
        <? $APPLICATION->ShowPanel(); ?>
        <header id="header">
        </header>