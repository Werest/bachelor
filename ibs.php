<?
/*
Модуль интеграции с ИБС
*/

function change($g)
{
    $enc = mb_detect_encoding($g);
    $g = mb_convert_encoding($g,$enc,'windows-1251');

    return $g;
}

$connection = mssql_connect('***', '***', '***');

if (!$connection) {
  die('Unable to connect!');
}

if (!mssql_select_db('Trud', $connection)) {
  die('Unable to select database!'); 
}
$fio_for_query = iconv('utf-8', 'windows-1251', $arResult["NAME"]);

$text_query = "SELECT CAST([Zaglavie] AS TEXT) AS Zag
                    , Soavtor 
                    , Zaglavie_prod
                    , Zaglavie_prod_dis
                    , Forma_rabot
                    , Meropr_mesto
                    , Meropr_data
                    , Stat_istichnik
                    , Stat_data
                    , Stat_nomer
                    , Stranic
                    , Gorod
                    , Izdatel
                    , God

                    FROM [Trud].[dbo].[RETURNME] ('$fio_for_query')";

$result = mssql_query($text_query,$connection) or die ("MSG: ".mssql_get_last_message());

if (mssql_num_rows($result)) {

echo "<br><hr>";
echo "<h3>Список трудов</h3>";

    $number_bib=1;
echo "<div style='line-height: 1.5'>";
    while ($row = mssql_fetch_array($result, MSSQL_ASSOC)) {
/*****************Вывод данных*********************************/
$end_el = end($row);

        if(!empty($row["Soavtor"]))
        {
            $Soavtor = $row["Soavtor"];
        }
        echo "<div>";
            echo "<p>";
        if(!empty($row["Zag"])){
echo $number_bib . ". ";
                echo "<b>".change($row["Zag"])."</b>";

            if(!empty( $Soavtor )){
                echo " / ".  change($Soavtor);
            }
            if(!empty( $row["Gorod"] )){
                echo " .- ". change($row["Zaglavie_prod"]);
            }
            if(!empty( $row["Gorod"] )){
                echo " - " . change($row["Gorod"]);
            }
            if(!empty( $row["Izdatel"] )){
                echo ": "  . change($row["Izdatel"]);
            }
            if(!empty( $row["God"] )){
                echo " , " . change($row["God"]) . ".";
            }
            if(!empty( $row["Stranic"] )){
                echo " - " . change($row["Stranic"]) . " с.";
            }

        }
/*****************Вывод данных*********************************/
$name_bib_mar = $row['Zag'];

$query_marc = "SELECT * FROM [MarcNIIPeriod].[dbo].[returnblobme] ('$name_bib_mar') ";

$result_marc = mssql_query($query_marc, $connection) or die ("MSG: ".mssql_get_last_message());

if (mssql_num_rows($result_marc)) {
$name_bib_mar = change($row['Zag']);

//Составляем ссылку на библиотеку
echo " [<a href='http://biblio.norvuz.ru/MarcWeb/MObjectDown.asp?MacroName=$name_bib_mar&MacroAcc=&DbVal=41' target='_blank'>Скачать в библиотеке</a>]";
}
    //while($row_marc = mssql_fetch_array( $result_marc, MSSQL_ASSOC )){
/***************************SAVE FILE*****************************/
    //$file_save_to = change($row_marc["NAME"]).".".$row_marc["TYP"];
    //$file_path = $_SERVER['DOCUMENT_ROOT']."/cim"."/docs/".$file_save_to;
    //if(!file_exists($file_path))
    //{
    //RewriteFile($file_path, $row_marc["ITEM"]);
    //  file_put_contents($file_path, $lococ);
    //}
    //  $file_path = "/cim"."/docs/".$file_save_to;
    //echo $file_path;
    //echo " [<a href='$file_path' target='_blank'>Загрузить</a>]";
/***************************SAVE FILE*****************************/
    //}

            echo "</p>";
        
        echo "</div>";
        //if(!empty($value)){echo change($value);}
        //echo $key . '=&gt;' . change($value) . '<br>';
    //}//number_rows
        $number_bib++;

    }
}
echo "</div>";
mssql_free_result($result);
?>

