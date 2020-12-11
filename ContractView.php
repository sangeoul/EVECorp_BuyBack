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
</style>
    
<?php

include $_SERVER['DOCUMENT_ROOT']."/CorpESI/shrimp/phplib.php";
dbset();


$n=$_GET["number"];
$result=$dbcon->query("select * from Industry_Contracts where contract_id=".$n." order by quantity desc;");
$data=array();
$sum=0;
$reprocessed=array();
$rep_index=array();
$reprocessed_buy=0.0;
echo("<table>\n");
for($i=0,$indexx=0;$i<$result->num_rows;$i++){

    $data[$i]=$result->fetch_array();
    echo("<tr>\n");
    echo("<td>".$data[$i]["itemname"]."</td>\n");
    echo("<td>".$data[$i]["quantity"]."</td>\n");
    echo("<td>".number_format($data[$i]["price"],2)."</td>\n");
    echo("<td>".number_format($data[$i]["buyprice"],2)."</td>\n");
    echo("<td>".number_format($data[$i]["price"]*100/$data[$i]["buyprice"],2)." %</td>\n");
    echo("</tr>\n");
    $sum+=$data[$i]["price"];
    
    $qr="select item_to_id,item_to_quantity,item_from_quantity,convert_rate from Industry_Relation where relation_type=1 and item_from_id=".$data[$i]["typeid"].";";
    $repro_result=$dbcon->query($qr);
    //errordebug($qr." q:".$data[$i]["quantity"]);
    
    for($j=0;$j<$repro_result->num_rows;$j++){
        $repro_data=$repro_result->fetch_row();
        if(isset($reprocessed["".$repro_data[0]])){
            //errordebug(getItemName($repro_data[0])." : ".$repro_data[1]."*".$data[$i]["quantity"]."*".$ORE_YIELD_RATE);
            $reprocessed["".$repro_data[0]]+=$repro_data[1]*$data[$i]["quantity"]*$repro_data[3]/$repro_data[2];
        }
        else{
            //errordebug(getItemName($repro_data[0])." : ".$repro_data[1]."*".$data[$i]["quantity"]."*".$ORE_YIELD_RATE);
            $reprocessed["".$repro_data[0]]=$repro_data[1]*$data[$i]["quantity"]*$repro_data[3]/$repro_data[2];
            $rep_index[$indexx]=$repro_data[0];
            $indexx++;
        }
        
    }

}
for($indexx=0;$indexx<sizeof($rep_index);$indexx++){
    $bqr="select price from Industry_Marketorders where typeid=".$rep_index[$indexx]." and quantity>0 and is_buy_order=1 order by time desc, price desc limit 2;";
    $bqr_result=$dbcon->query($bqr);
    if($bqr_result->num_rows>0){
        $buyprice=$bqr_result->fetch_row();
        $reprocessed_buy+=floor($reprocessed["".$rep_index[$indexx]])*$buyprice[0];
        //errordebug(getItemName($rep_index[$indexx])." : ".number_format($reprocessed["".$rep_index[$indexx]])." * ".$buyprice[0]);
    }       
}
echo("<table><br>\n");

?>
<br>
<table>
<tr>
<td onclick="javascript:showform()">Sum</td><td> : <?=number_format($sum,2)?></td>
<tr></tr>
<td>Reprocessed Buy Value </td><td> :<?=number_format($reprocessed_buy,2)?></td> 
</tr>
</table><br><br>

<form method="post" action="ContractAccept.php?" id=hform >
<input type=password id="aa" name="aa" hidden>
<input type=hidden id="num" name="num" value=<?=$n?>>
<input type=submit value="blank" id=sub name=sub hidden>
</form>
<script>

function showform(){
    document.getElementById('aa').style.display='block';
    document.getElementById('sub').style.display='block';
}
</script>
