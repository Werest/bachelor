<?
/**Модуль обработки данных из 1С:ЗКГУ 
 * Разработана Чигридовым К.А.
 * Логика приложения разработана Горбуновым А.В.
 * Версия 1.1.4 (release)
 * Последняя дата 28.05.18
 */

header("Content-Type: text/html; charset=UTF-8");
ini_set("soap.wsdl_cache_enabled", "0");

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
//Подключаем модуль работы с инфоблоками
CModule::IncludeModule("iblock");
//IncludeModuleLangFile(__FILE__);
//устанавливаем время работы скрипты максимальным
set_time_limit(10000);

//Попытка подключиться к web сервису 1С
try {
echo "\nНачата обработка персон из 1С";

$wsdl_link = "***";
$client_soap=new SoapClient($wsdl_link, array('login'           => "***",
                                              'password'          => "***",
'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP));

echo "\nПодключение прошло успешно к 1С";

//Обращаемся к функции в веб сервисе, получаем массив и записываем в переменную
$tyty_all=$client_soap->Newall();
//закрываем соединение с 1С
$client_soap=null;

echo "\nДанные по персонам записаны в переменную";
//Выносим из инфоблока даннные в массив для дальшешего использования
$idIblock = 1;
    $arSelect = Array("ID", "NAME");
    $arFilter = Array("IBLOCK_ID"=>IntVal($idIblock), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    while($ob = $res->GetNextElement()){
        $arFields = $ob->GetFields();
        $arElements[] = $arFields["NAME"];  //ФИО с ифоблока персоналии
        $arID[] = $arFields["ID"]; // ID из персоналий
    }


echo "\nДанные разносятся по ассоциативным массивам";

//Обрабатываем данные из 1С и разносим по ассоциативному массиву
foreach($tyty_all->return->row as $aha)
{
    foreach($aha as $gh)
    {
        if(!empty($gh[1])){
            if(!array_search($gh[0], $fio_fiz["fio"])){
            $fio_fiz["fio"][] = $gh[0];

            $fio_fiz["work"][] = $gh[1];
            $fio_fiz["place_work"][] = $gh[2];

            $fio_fiz["zvan"][] = $gh[3];
            $fio_fiz["step"][] = $gh[4];
            $fio_fiz["obrz"][] = $gh[5];

            }
            //если нашли дубль
            else 
            {
                $id_to[] = array_search($gh[0], $fio_fiz["fio"]);
                $fio_fiz["fio"][] = $gh[0];

                $fio_fiz["work"][] = $gh[1];
                $fio_fiz["place_work"][] = $gh[2];
    
                $fio_fiz["zvan"][] = $gh[3];
                $fio_fiz["step"][] = $gh[4];
                $fio_fiz["obrz"][] = $gh[5];
            }       
        }
    }

}
$count_duplicate = count($id_to);
echo "\nНайдено дублей = " . $count_duplicate;
asort($id_to);
for($i=0; $i<$count_duplicate; $i++)
{

    foreach($fio_fiz as $key => $jh){
        

        if($fio_fiz[$key][$id_to[$i]]!=$fio_fiz[$key][$id_to[$i]+1] && $key!="fio")
        {
            $fio_fiz[$key][$id_to[$i]] .= "/".$fio_fiz[$key][$id_to[$i]+1];
        }

        unset($fio_fiz[$key][$id_to[$i]+1]);
    }
    //т.к. убрали одну позицию, к позиции которая будет следующая для поиска
    //прибавляем +1
    $id_to[$i+1]=$id_to[$i+1]-$i-1;
    foreach($fio_fiz as $keys=>$kk)
    {
        $fio_fiz[$keys] = array_values($fio_fiz[$keys]);
    }


    
}

$count=count($fio_fiz["fio"]);

//начинаем подсчёт добавленных и обновленных персон
$count_add=0;
$count_upp=0;

$count=count($fio_fiz["fio"]);
echo "\nКоличество персон = " . $count;

for($i=0; $i<$count; $i++)
{
$fio_ex = explode("/", $fio_fiz["fio"][$i]);
$fio_end = $fio_ex[0]; 
$key_s = array_search($fio_end, $arElements);
$ID_fio = $arID[$key_s];



$date = date("d.m.Y H:m:s");
    if($key_s== false and is_bool($key_s)) {


$PROP = array();
    $PROP[1] = $fio_fiz["work"][$i];  // Должность
    $PROP[507] = $fio_fiz["place_work"][$i]; //место работы
    $PROP[6] = $fio_fiz["step"][$i];  // Степень
    $PROP[7] = $fio_fiz["zvan"][$i];  // Звание
    $PROP[493] = $date;  // Date

$arLoadProductArray = Array(
  "MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
  "IBLOCK_SECTION_ID" => false,          // элемент лежит в корне раздела
  "IBLOCK_ID"      => 1,
  "PROPERTY_VALUES"=> $PROP,
  "NAME"           => $fio_end,
  "ACTIVE"         => "Y"            // активен
  );

try
{
    //Определяем эксземляр класса CIBlockElement
    $el = new CIBlockElement;
    //Добавляем элемент в инфоблок
    $PRODUCT_ID = $el->Add($arLoadProductArray);
    $up_add = "ADD".$PRODUCT_ID;

}catch(Exception $e){
    //Если была выявлена ошибка
    $up_add = "Error: ".$el->LAST_ERROR;
}
//Подсчитываем количество добавлен персон
$count_add++;

    }else{
        $change_personal = array(
        "post"      => $fio_fiz["work"][$i],
        "place_work"=> $fio_fiz["place_work"][$i],
        "degree"    => $fio_fiz["step"][$i],
        "academStat"=> $fio_fiz["zvan"][$i],
        "dateedit"  => $date
        );
        //Обновляем свойства инфоблока, не обнуляя при этом другие
        CIBlockElement::SetPropertyValuesEx($ID_fio, false, $change_personal);
        $up_add="UPDATE".$date;
    $count_upp++;
    }

}//for

echo "\n\nОкончена обработка персон из 1С. Обработано = " . $count . " персон";
    echo "\nДобавлено: " . $count_add;
    echo "\nОбновлено: " . $count_upp;

mail('weresttrade@ya.ru', 'Работа модуля 1С:ЗКГУ', 'Модуль был выполнен. ' . "\nДобавлено: " . $count_add . "\nОбновлено: " . $count_upp);

}//try
catch(Exception $ex){
    echo "Рекомендуем посмотреть на ошибку на <a href=' " . $wsdl_link . "'>WSDL</a>";
    die('Ошибка в подключение по SOAP: \n<b>'.$ex->getMessage().'</b>');
}
?>





