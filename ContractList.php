<style>

table{
    border:1px solid black;
    border-collapse:collapse;
}
td,th{
    border:1px solid black;
    border-collapse:collapse;
}

span.totalprice{
    font-size:30px;
    color: #1338BE;
}

span.cont1{
    color:rgba(160,30,30,1);
    font-weight:bold;
}
span.cont2{
    color:rgba(20,130,40,1);
}
</style>
<!-- 구글애드센스-->
<script data-ad-client="ca-pub-7625490600882004" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>    

<?php

include $_SERVER['DOCUMENT_ROOT']."/CorpESI/shrimp/phplib.php";

dbset();
logincheck();
echo $addsense;

$minerals_result=$dbcon->query("select quantity from Industry_Contracts where contract_id=1 and typeid>33 and typeid<41 order by typeid asc");
for($i=0;$i<$minerals_result->num_rows;$i++){
    $minerals[$i]=$minerals_result->fetch_array();
}


$result= $dbcon->query("select contract_id,username,sum(price) as sss,is_buy from Industry_Contracts where contract_id>1 group by contract_id,username,is_buy order by contract_id desc limit 100");

echo("<a href=\"./SubmitContract.php\" style=\"font-size:30px;\" target=\"_blank\">바이백 컨트랙 신청하기</a><br>\n");
echo("컨트랙은 Ubuntu Hakurei 에게 Y-MPWL <-> Jita 경로상의 모든 성계  에서 삽니다.<br>\n");
//echo("현재는 테스트중입니다.<br>\n");
echo("<br>\n");
/*echo("미네랄 수요.(해당 광 매입가 높음)<br>\n");
echo("Tritanium : ".number_format(max($minerals[0][0],0))."<br>\n");
echo("Pyerite : ".number_format(max($minerals[1][0],0))."<br>\n");
echo("Mexallon : ".number_format(max($minerals[2][0],0))."<br>\n");
echo("Nocxium : ".number_format(max($minerals[3][0],0))."<br>\n");
echo("Isogen : ".number_format(max($minerals[4][0],0))."<br>\n");
echo("Zydrine : ".number_format(max($minerals[5][0],0))."<br>\n");
echo("Megacyte : ".number_format(max($minerals[6][0],0))."<br>\n");
*/

echo("<table>");
echo("<tr><th>#</th><th>신청인</th><th>금액</th></tr>");
for($i=0;$i<$result->num_rows;$i++){
    $contractdata=$result->fetch_array();
    echo("<tr>\n");
    echo("<td><span onclick=\"javascript:window.open('./ContractView.php?number=".$contractdata["contract_id"]."','_blank');\">".$contractdata["contract_id"]."</span></td>");
    echo("<td>".$contractdata["username"]."</td><td><span class=\"cont".$contractdata["is_buy"]."\">".number_format($contractdata["sss"])."</span></td>\n");
    echo("</tr>\n");
}
echo("</table>");

?>