<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();
?>

<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$arComponentDescription = array(
        "NAME" => GetMessage("USER_COMPONENT_NAME"),
        "DESCRIPTION" => GetMessage("USER_COMPONENT_DESCR"),
        //"ICON" => "/images/icon.gif",
        "COMPLEX" => "Y",
        "PATH" => array(
                "ID" => "kurkin_picture",
                "NAME" => GetMessage("USER_SECTION_NAME"),
                "CHILD" => array(
                        "ID" => "kurkin_picture_serv",
                        "NAME" => GetMessage("USER_SERVICE"),
                        "SORT" => 30,
                ),
        ),
);
?>
