<div id="progressbar">
    <div id="dauther"></div>
    
</div>
<div id="main_info"></div>

<?
$db_host = "***";
$db_user = "***";
$db_pass = "***";
$db_name = "***";

$link = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$link) { 
   printf("Невозможно подключиться к базе данных: %s\n", mysqli_connect_error()); 
   exit; 
} else
{
        mysqli_query($link, "set character_set_client='utf8'");
        mysqli_query($link, "set character_set_results='utf8'");
        mysqli_query($link, "set collation_connection='utf8_general_ci'");
}
include_once('simple_html_dom.php');
function curl_get($url, $referer='http://www.google.com')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    //интерес к заголовкам 0-нет, 1-да
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //как будто пришли через браузер
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36');
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

// проверим встречается ли подстрока в строке
 function is_in_str($str,$substr)
 {
   $result = strpos ($str, $substr);
   if ($result === FALSE) // если это действительно FALSE, а не ноль, например 
     return false;
   else
     return true;   
 }
 
 function have_tag($rts)
 {
     $ts=0;
    if(strlen(trim($rts))>0)
        $ts=1;
    else $ts=0;
    return $ts;
 }


 $array_part        = array('common','struct','document','eduStandarts','grants','paid_edu','education','employees','objects','budget','vacant');
 $array_name_p  = array("Основные сведения"
 ,"Структура и органы управления образовательной организацией"
 ,"Документы"
 ,"Образовательные стандарты"
 ,"Стипендии и иные виды материальной поддержки"
 ,"Платные образовательные услуги"
 ,"Образование"
 ,"Руководство. Педагогический (научно-педагогический) состав"
 ,"Материально-техническое обеспечение и оснащѐнность образовательного процесса"
 ,"Финансово-хозяйственная деятельность"
 ,"Вакантные места для приема(перевода)");

//echo '<form action="" method="post">';
//echo '<button name="f5" type="submit" value="checking_me">Обновить</button>';
//echo '</form>';

if(!empty($_POST['f5']))
{
    foreach($array_part as $trr)
    {
        $path_to_files = getcwd();

        unlink($path_to_files . "/" . $trr);
    }
    unset( $_POST['f5']);
}

foreach($array_part as $trr)
{
    if(file_exists($trr) && date("H")==date("H", filemtime($trr))){
        $html=file_get_contents($trr);
    }else{
        $html = curl_get("http://norvuz.ru/sveden/".$trr."/");
        //$html = curl_get("http://www.sfu-kras.ru/sveden/".$trr."/");
        file_put_contents($trr,$html);
    }
}
//имеются тегов
$number_tag=0; 
//тегов с информацией
$number_tag_w_info=0;
//всего проставлено тегов включая повторяющиеся
$count_tag=0;
//всего проставлено тегов включая повторяющиеся с информацией
$count_tag_w_info=0;

foreach($array_part as $trr)
{
    $key_array_part=array_search($trr,$array_part);
    echo "<hr>";
    echo "Последнее время обновления: " . date("H:i:s", filemtime($trr))."--".$array_name_p[$key_array_part];
    echo "<hr>";
    
    $html=file_get_contents($trr);
    $dom = str_get_html($html);

    $result = mysqli_query($link, "SELECT * FROM `table` WHERE `place`='".$trr."'");
    
    $id_table = 'table-'.$key_array_part;
    echo "<button class='button' onclick=toogle_table('$id_table')>".$array_name_p[$key_array_part]."</button>";

echo "<table style=display:none id='$id_table' class='rtable'>";
    echo
    '
    <th>Тег</th>
    <th>Информация</th>
    <th>Есть тег</th>
    <th>Наличие информации</th>
    ';
    $ut=0; $have_text=0; $exit_exit=0;$sum_tag=0;
    $sum_have_text_tag=0;
    
        while($u = mysqli_fetch_array($result))
        {
            
        
            $ddd = $dom->find('['.$u["tag"].']');   

                if($ddd){
                    foreach($ddd as $write)
                    {
                        echo '<tr>';
                        
                            echo '<td style="background-color:green;">';
                            echo $write->itemprop; 
                            echo '</td>';
                            echo '<td>';
                            echo $write->plaintext;
                            echo '</td>';
                            echo '<td>';
                            echo have_tag($write->itemprop);
                            
                            echo '</td>';
                            echo '<td ';if(have_tag($write->plaintext)==0){echo "style=background-color:red;";}echo '>';
                            echo have_tag($write->plaintext);
                            echo '</td>';
                            
                        echo '</tr>';
                        $sum_tag++;
                        $sum_have_text_tag+=have_tag($write->plaintext);
                    }
                    $ut+=have_tag($write->itemprop);
                    $have_text+=have_tag($write->plaintext);
                }else{
                    echo '<tr>';
                    echo '<td style="background-color:red;">' . $u["tag"] . '</td>';
                    echo '<td>' . $u["info"] . '</td>';
                    echo '<td>' . "нет" . '</td>';
                    echo '<td>' . "нет" . '</td>';
                    echo '</tr>';
                }   
            $exit_exit++;
        }
    
    echo '</table>';
    echo "<br>" . "Имеются тегов: " . $ut;
    echo "<br>" . "Информация в тегах: " .$have_text;
    echo "<br>" . "Всего тегов: " .$exit_exit;
    echo "<br>" . "Процент: " . floor(($ut/$exit_exit)*10000)/100;
    echo "<br>" . "Всего проставлено тегов: " . $sum_tag;
    echo "<br>" . "Всего проставлено тегов имеющие информацию: " . $sum_have_text_tag;
    
    
    $number_tag+=$ut;//Имеются тегов
    $number_tag_w_info+=$have_text;//Информация в тегах
    $exit_exit_sum+=$exit_exit;//Всего тегов
    
    $count_tag+=$sum_tag;//наращиванье всего проставлено
    $count_tag_w_info+=$sum_have_text_tag;//наращиванье всего проставлено с информацией
    
    
    if($sum_tag!=0)
    {
        echo "<br>" . "Коэффициент заполнености тега: " . floor(($sum_have_text_tag/$sum_tag)*10000)/100;
    }else{
        echo "<br>" . "Коэффициент заполнености тега: 0";
    }

    echo "<br>";
    //return;
}
    $percent_end = floor(($number_tag/$exit_exit_sum)*10000)/100;
    
    echo "<p id='number_tag' style='display:none;'>".$number_tag."</p>";
    echo "<p id='number_tag_w_info' style='display:none;'>".$number_tag_w_info."</p>";
    echo "<p id='percent_end' style='display:none;'>".$percent_end."</p>";

mysqli_close($link);

$end_count_tag=0;

    if($count_tag!=0)
    {
        $end_count_tag=floor(($count_tag_w_info/$count_tag)*10000)/100;
    }else{
        $end_count_tag=0;
    }

echo '<hr>';
//echo "<br>" . date("H:i:s", filemtime('result.txt'));


function matlab_fis(){
    //$direction = getcwd();
    $command_on_exec = "CFI_v_02.exe";
    //exec($command_on_exec);

    //shell_exec('taskkill /F /IM "CFI_v_02.exe"');
    $c = file_get_contents('res.txt');
    return $c;
    //$cmd = "\"\\Matlab2016R\\bin\\matlab.exe\ quit;";
    //exec($cmd);
}
$matlab_fis = matlab_fis();

?>

<script>
function toogle_table(tablenumber)
{
    var tb = "#" + tablenumber;
    if($(tb).is(':visible')){
        $.cookie(tb,false);         
    }
    if($(tb).is(':hidden')){
        $.cookie(tb, true); 
    }
    $( tb ).toggle("slow");
}

$(document).ready(function() {

    var length_table = $(".rtable").length;
    for (i=0; i<length_table;i++)
        {
            if(($.cookie("#table-" + i))=="true")
            {
                $("#table-" + i).toggle(true);
            }           
        }
        $("#dauther").width(<?=$percent_end;?>+"%");
        $("#main_info").html("Общий процент заполнености  "+<?=$percent_end;?>+"%" + "<br><b>Нечеткий вывод: </b>" + <?=$matlab_fis;?> + "%");
    
});

</script>