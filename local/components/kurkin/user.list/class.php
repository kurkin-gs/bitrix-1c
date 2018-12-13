<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

use Bitrix\Main\Entity;

class CKurkinUserComponent extends CBitrixComponent {

    public $delimiterCSV = ";";
    public $tempFolder = "/upload/user_list_export/";

    public function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getSelect($key = "DISPLAY_FIELDS") {
        if (!empty($this->arParams[$key]))
            $select = (array) $this->arParams[$key];
        else
            $select = array('*');
        return $select;
    }

    public function getSort($key = "DISPLAY_FIELDS") {
        $sort_by = 'ID';
        $sort_order = 'DESC';

        if (!empty($this->arParams["SORT_BY"]) && (isset($this->arParams[$key][$this->arParams["SORT_BY"]]))) {
            $sort_by = $this->arParams["SORT_BY"];
        }

        if (!empty($this->arParams["SORT_ORDER"]) && in_array($this->arParams["SORT_ORDER"], array('ASC', 'DESC'), true)) {
            $sort_order = $this->arParams["SORT_ORDER"];
        }

        if ($sort_by && $sort_order)
            $sort = array($sort_by => $sort_order);
        else
            $sort = array();

        return $sort;
    }

    public function createTempFolder() {
        if (!file_exists($this->server["DOCUMENT_ROOT"] . $this->tempFolder)) {
            mkdir($this->server["DOCUMENT_ROOT"] . $this->tempFolder, 0777);
        }
    }

    //удаление старых файлов из временного каталога
    public function removeOldFiles() {
        if (!$this->tempFolder || count(explode("/", $this->tempFolder)) < 3)
            return false;
        if ($handle = opendir($this->server["DOCUMENT_ROOT"] . $this->tempFolder)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $filename = $this->server["DOCUMENT_ROOT"] . $this->tempFolder . $entry;
                    if (!file_exists($filename))
                        continue;
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (!in_array($ext, array("csv", "xml")))
                        continue;
                    if ((time() - filemtime($filename)) / 60 / 60 >= 12)
                        unlink($filename);
                }
            }
            closedir($handle);
        }
    }

    public function jsonResponse($data) {
        ob_end_clean();
        header('Content-type: application/json');
        echo json_encode($data);
        exit;
    }

    public function arrayToCsv(array &$fields, $delimiter = ';', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false) {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');

        $output = array();
        foreach ($fields as $field) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            if ($encloseAll || preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field)) {
                $output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
            } else {
                $output[] = $field;
            }
        }

        return implode($delimiter, $output);
    }

    public function saveAsCSV($hash = false) {
        if (strlen($hash) > 0 && !array_key_exists($hash, $_SESSION["KURKIN_USER_CSV_EXPORT"]))
            return false;
        $this->createTempFolder();
        $start_time = time();

        if (!$hash) {
            $hash = md5(date("d-m-Y H:i:s") . $this->generateRandomString(10));
            $_SESSION["KURKIN_USER_CSV_EXPORT"][$hash] = array("filename" => $hash, "step" => 1, "offset" => 0);
        }

        $session = &$_SESSION["KURKIN_USER_CSV_EXPORT"][$hash];

        $this->fp = $this->server["DOCUMENT_ROOT"] . $this->tempFolder . "{$session["filename"]}.csv";

        if ($session["step"] <= 2)
            $handle = fopen($this->fp, $session["step"] == 1 ? "w" : "a"); //на первом шаге создаем файл, на последующих используем существующий

        if ($session["step"] == 1) {
            fwrite($handle, $this->arrayToCsv($this->arParams["EXPORT_FIELDS"], $this->delimiterCSV) . "\n");
            $session["step"] ++;
        }

        if ($session["step"] == 2) {
            $select = $this->getSelect("EXPORT_FIELDS");
            $sort = $this->getSort("EXPORT_FIELDS");
            $maxRecord = \Bitrix\Main\UserTable::GetList(array(
                        'select' => array(new Entity\ExpressionField('CNT', 'COUNT(*)'))
                    ))->fetch()["CNT"];
            do {
                $result = \Bitrix\Main\UserTable::GetList(array("select" => $select, "order" => $sort, "limit" => $this->arParams["EXPORT_STEP_LIMIT"], "offset" => $session["offset"]));
                $counter = 0;
                while ($row = $result->fetch()) {
                    fwrite($handle, $this->arrayToCsv($row, $this->delimiterCSV) . "\n");
                    $counter++;
                    if ($this->arParams["EXPORT_INTERVAL"] > 0 && (time() - $start_time) > $this->arParams["EXPORT_INTERVAL"]) {
                        $session["offset"] += $counter;
                        $this->jsonResponse(array("complete" => false, "progress" => number_format(100 / $maxRecord * $session["offset"], 2), "hash" => $hash));
                    }
                }
                $session["offset"] += $counter;
            } while ($session["offset"] < $maxRecord);
            $session["step"] ++;
            fclose($handle);
            $this->jsonResponse(array("complete" => true, "progress" => number_format(100 / $maxRecord * $session["offset"], 2), "hash" => $hash));
        }

        if ($session["step"] == 3) {
            $content = file_get_contents($this->fp);
            unlink($this->fp);
            unset($_SESSION["KURKIN_USER_CSV_EXPORT"][$hash]);

            //вывод файла
            ob_end_clean();
            $filename = "export " . date("d-m-Y H:i:s");
            header("Content-type: text/csv");
            header("Content-Disposition: attachment; filename={$filename}.csv");
            header("Pragma: no-cache");
            header("Expires: 0");

            echo $content;
        }
        exit;
    }

    public function saveAsXML($hash = false) {
        if ($hash && !array_key_exists($hash, $_SESSION["KURKIN_USER_XML_EXPORT"]))
            return false;
        $this->createTempFolder();
        $start_time = time();

        if (!$hash) {
            $hash = md5(date("d-m-Y H:i:s") . $this->generateRandomString(10));
            $_SESSION["KURKIN_USER_XML_EXPORT"][$hash] = array("filename" => $hash, "step" => 1, "offset" => 0);
        }

        $session = &$_SESSION["KURKIN_USER_XML_EXPORT"][$hash];

        $this->fp = $this->server["DOCUMENT_ROOT"] . $this->tempFolder . "{$session["filename"]}.xml";

        if ($session["step"] <= 2)
            $handle = fopen($this->fp, $session["step"] == 1 ? "w" : "a");

        if ($session["step"] == 1) {
            fwrite($handle, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>");
            fwrite($handle, "<USERS>");
            $session["step"] ++;
        }
        if ($session["step"] == 2) {
            $select = $this->getSelect("EXPORT_FIELDS");
            $sort = $this->getSort("EXPORT_FIELDS");
            $maxRecord = \Bitrix\Main\UserTable::GetList(array(
                        'select' => array(new Entity\ExpressionField('CNT', 'COUNT(*)'))
                    ))->fetch()["CNT"];

            do {
                $result = \Bitrix\Main\UserTable::GetList(array("select" => $select, "order" => $sort, "limit" => $this->arParams["EXPORT_STEP_LIMIT"], "offset" => $session["offset"]));

                $counter = 0;
                while ($row = $result->fetch()) {
                    $output = "";

                    foreach ($row as $key => $field) {
                        $output.=(strlen($field) > 0) ? "<{$key}>" . $field . "</{$key}>" : "<{$key}/>";
                    }

                    fwrite($handle, "<USER>" . $output . "</USER>");
                    $counter++;
                    if ($this->arParams["EXPORT_INTERVAL"] > 0 && (time() - $start_time) > $this->arParams["EXPORT_INTERVAL"]) {
                        $session["offset"] += $counter;
                        $this->jsonResponse(array("complete" => false, "progress" => number_format(100 / $maxRecord * $session["offset"], 2), "hash" => $hash));
                    }
                }

                $session["offset"] += $counter;
            } while ($session["offset"] < $maxRecord);

            fwrite($handle, "</USERS>");
            fclose($handle);
            $session["step"] ++;
            $this->jsonResponse(array("complete" => true, "progress" => number_format(100 / $maxRecord * $session["offset"], 2), "hash" => $hash));
        }
        if ($session["step"] == 3) {

            $filename = "export " . date("d-m-Y H:i:s");

            $xml = simplexml_load_file($this->fp);
            unlink($this->fp);
            unset($_SESSION["KURKIN_USER_XML_EXPORT"][$hash]);

            //стилизуем xml отступами
            $dom = dom_import_simplexml($xml)->ownerDocument;
            $dom->formatOutput = true;

            ob_end_clean();
            header("Content-type: text/xml");
            header("Content-Disposition: attachment; filename={$filename}.xml");
            header("Pragma: no-cache");
            header("Expires: 0");
            echo $dom->saveXML();
        }
        exit;
    }

    public function onPrepareComponentParams($params) {
        //формирование параметров
        if (!isset($params["CACHE_TIME"]))
            $params["CACHE_TIME"] = 36000000;

        $params["DISPLAY_TOP_PAGER"] = $params["DISPLAY_TOP_PAGER"] == "Y";
        $params["DISPLAY_BOTTOM_PAGER"] = $params["DISPLAY_BOTTOM_PAGER"] != "N";

        $fields = \Bitrix\Main\UserTable::GetMap();
        $arProperty = array();
        foreach ($fields as $key => $field) {
            $arProperty[$key] = $key;
        }

        foreach (array("DISPLAY_FIELDS", "EXPORT_FIELDS") as $key) {
            $params[$key] = (array) $params[$key];
            if (empty($params[$key]))
                $params[$key] = $arProperty;
            else
                $params[$key] = array_intersect($params[$key], $arProperty);

            if (!in_array("LOGIN", $params[$key]))
                array_unshift($params[$key], "LOGIN");
        }

        $params["EXPORT_INTERVAL"] = (int) $params["EXPORT_INTERVAL"];
        $params["EXPORT_STEP_LIMIT"] = (int) $params["EXPORT_STEP_LIMIT"];
        if (!$params["EXPORT_STEP_LIMIT"])
            $params["EXPORT_STEP_LIMIT"] = 3000; //шаг по умолчанию
        else
        if ($params["EXPORT_STEP_LIMIT"] > 0 && $params["EXPORT_STEP_LIMIT"] < 1000)
            $params["EXPORT_STEP_LIMIT"] = 1000; //минимальный шаг

        return $params;
    }

    public function executeComponent() {

        if (!$this->arParams["EXPORT_INTERVAL"])
            set_time_limit(0);

        $context = \Bitrix\Main\Application::getInstance()->getContext();
        $this->request = $context->getRequest();
        $this->server = $context->getServer();

        CPageOption::SetOptionString("main", "nav_page_in_session", "N"); //не храним номер страницы в сессии
        CUtil::InitJSCore(Array("jquery", "ajax")); //подключаем для работы с аяксом
        //шаг 1 - создание файла, запись заголовков в файл
        //шаг 2 - выборка из базы, запись в файл
        //шаг 3 - загрузка файла, удаление файла из временной папки
        //экспорт в csv
        if ($this->request["save_csv"]) {
            $this->saveAsCSV($this->request["hash"]);
        }
        //экспорт в xml
        if ($this->request["save_xml"]) {
            $this->saveAsXML($this->request["hash"]);
        }

        //удаление файлов старше определенного времени из временной папки, оставшихся после прерванного экспорта
        $this->removeOldFiles();

        $select = $this->getSelect();
        $sort = $this->getSort();

        $this->limit = (int) $this->arParams["ROWS_PER_PAGE"] > 0 ? (int) $this->arParams["ROWS_PER_PAGE"] : 10;
//определяем навигацию, для получения текущего номера страницы, и соответственно параметра offset
        $this->nav = new \CDBResult();
        $this->nav->NavStart($this->limit);
        $this->offset = $this->limit * (int) $this->nav->PAGEN - $this->limit;

        if ($this->startResultCache(false, array($select, $sort, $this->limit, $this->offset))) {

            $result = \Bitrix\Main\UserTable::GetList(array("select" => $select, "order" => $sort, "limit" => $this->limit, "offset" => $this->offset));

            $this->arResult["ITEMS"] = array();

            while ($row = $result->fetch()) {
                $this->arResult["ITEMS"][] = $row;
            }

//получаем общее количество элементов для построения постраничной навигации
            $maxRecord = \Bitrix\Main\UserTable::GetList(array(
                        'select' => array(new Entity\ExpressionField('CNT', 'COUNT(*)'))
                    ))->fetch()["CNT"];
            $this->nav->NavPageCount = round($maxRecord / $this->limit);
            $this->nav->NavPageNomer = $this->nav->PAGEN ? $this->nav->PAGEN : 1;
            $this->nav->NavRecordCount = $maxRecord;
            $this->arResult["NAV_STRING"] = $this->nav->GetPageNavStringEx($navComponentObject, '', $this->arParams["PAGER_TEMPLATE"], 'Y');

            $this->IncludeComponentTemplate();
        }
    }

}
