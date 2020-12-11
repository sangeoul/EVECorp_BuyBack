<?php

include $_SERVER['DOCUMENT_ROOT']."/CorpESI/shrimp/phplib.php";

dbset() ;

// set password 
if($_POST["aa"]=="password"){

    $result=$dbcon->query("update Industry_Contracts set is_buy=2 where contract_id=".$_POST["num"].";");

    if($result){
        echo("Success.");
    }
    else{
        echo("Failure.");
    }
}
else{
    echo("Failure.");
}
    ?>