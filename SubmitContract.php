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

a.apply{
    font-size:22px;
    width:220px;
    height:30px;
    display:block;
    color:white;
    text-decoration:none;
    text-align:center;
    
    border:3px solid rgb(10,60,10);
    background-color:rgb(20,110,20);
    border-radius: 6px;
    

}

a.apply:hover{
    background-color:rgb(50,170,50);
}

</style>

<!-- 구글애드센스-->
<script data-ad-client="ca-pub-7625490600882004" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script> 

<form method=post action="./SubmitContract.php">
캐릭터 이름<br>
<input type=text id=username name=username size=20 maxlength=30><br>
아래에 아이템 리스트를 붙여넣기 하세요.(광, 얼음, PI물품) <br>
<textarea id=itemlist name=itemlist cols=50 rows=8></textarea>

<br><input type=submit value="견적 보기" style="width:100px;height:40px;font-size:20px;">
</form>
<?php

include $_SERVER['DOCUMENT_ROOT']."/CorpESI/shrimp/phplib.php";
dbset();

?>
<?php
/*
Compressed Viscous Pyroxeres	2,227	Pyroxeres			6,682.80 m3	850,720.44 ISK
Compressed Plagioclase	40,321	Plagioclase			14,112.35 m3	1,785,817.09 ISK
Compressed Massive Scordite	33,589	Scordite			5,038.35 m3	686,559.16 ISK
Compressed Dense Veldspar	9,603	Veldspar			960.30 m3	173,814.30 ISK
Compressed Condensed Scordite	38,120	Scordite			5,718 m3	728,854.40 ISK
Compressed Azure Plagioclase	7,109	Plagioclase			2,488.15 m3	326,516.37 ISK
*/
$itemarray=array();

if(isset($_POST["itemlist"]) && $_POST["itemlist"]!="" && !isset($_POST["submit"])) {
    //echo(" DEBUG : NORMAL ");
    
    $itemstring=explode("\n",$_POST["itemlist"]);
    
    for($i=0;$i<sizeof($itemstring);$i++){

        $itemstring[$i]=str_replace("    ","\t",$itemstring[$i]);
        $itemstring[$i]=explode("\t",$itemstring[$i]);
        $itemstring[$i][0]=str_replace("*","",$itemstring[$i][0]);
        
        $qr="select * from EVEDB_Item where itemname=\"".$itemstring[$i][0]."\";";
        $result=$dbcon->query($qr);
        if($result->num_rows>0){
            $namedata=$result->fetch_array();
            $itemstring[$i][0]=$namedata["typeid"];
        }
        else{
            $itemstring[$i][0]=0;
        }
        $itemstring[$i][1]=intval(str_replace(",","",$itemstring[$i][1]));
    }
}


else if (isset($_POST["submit"]) && $_POST["submit"]==1){

    $itemstring=json_decode($_POST['json']);
     
}
else if (isset($_GET["submit"]) && $_GET["submit"]==1){

    
    echo($_GET['json']."<br>\n");
    echo($_GET["total"]."<br>\n");
    $itemstring=json_decode($_GET['json']);
     
}
else{
    //echo(" DEBUG : NOTHING \n");
}

$mineralbuy=array(0,0,0,0,0,0,0);
$iceproductbuy=array(0,0,0,0,0,0,0);
$itembuy=array();
$itembuy_id=array();
$totalmineral=array(0,0,0,0,0,0,0);
$totalice=array(0,0,0,0,0,0,0);
$totalitem=array();
$compressed_ice=array();
$qr="select * from Industry_Contracts where is_buy=1";
$result=$dbcon->query($qr);

for($i=0;$i<$result->num_rows;$i++){
    $contractdata=$result->fetch_array();

    //미네랄은 따로 관리/계산
    if($contractdata["typeid"]>=34 && $contractdata["typeid"]<=40){
        $mineralbuy[($contractdata["typeid"]-34)]+=$contractdata["quantity"];
        
    }
    //아이스 계산
    else if(in_array($contractdata["typeid"],$ICE_PRODUCTS)){
        $iceproductbuy[iceproductArrayNum($contractdata["typeid"])]+=$contractdata["quantity"];
    }
    else{
        $itembuy[("".$contractdata["typeid"])]=$contractdata["quantity"];
        $itembuy_id[sizeof($itembuy_id)]=$contractdata["typeid"];
    }
}
$totalvalue=0;

for($i=0,$i_=0;$i<sizeof($itemstring);$i++){

    //압축광들은 따로 계산한다. 단 Mercoxit(groupid 468)의 경우는 매입하지않음. (465는 아이스)
    $qr="select * from EVEDB_Item where typeid=\"".$itemstring[$i][0]."\" and compressed=1 and groupid!=468 and groupid!=465;";
    $result=$dbcon->query($qr);
    
    //압축광이면
    if($result->num_rows>0) {

        $rep_minerals=array(0,0,0,0,0,0,0);
        $mineral_is_ok=array(doubleval(0),doubleval(0),doubleval(0),doubleval(0),doubleval(0),doubleval(0),doubleval(0));

        
        $oredata=$result->fetch_array();
        $qr="select * from Industry_Relation where relation_type=1 and item_from_id=".$oredata["typeid"].";";
        $result=$dbcon->query($qr);

        //바이가를 계산한다.
        $qr="select price from Industry_Marketorders where typeid=".$oredata["typeid"]." and is_buy_order=1 and quantity>0 order by time desc, price desc limit 2;";
        $buypriceresult=$dbcon->query($qr);

        if($buypriceresult->num_rows>0){
            $buyprice=$buypriceresult->fetch_array();
        }
        else{
            $buyprice=array(0);
        }
        //판매하려는 압축광을 정제했을 때의 미네랄양을 계산한다.
        for($j=0;$j<$result->num_rows;$j++){

            $mineraldata=$result->fetch_array();
            $rep_minerals[($mineraldata["item_to_id"]-34)]+=floor($mineraldata["item_to_quantity"]*$itemstring[$i][1]*$mineraldata["convert_rate"]);
            //echo("DEBUG : ".$mineraldata["item_from_name"]." x ".$itemstring[$i][1]." -> ".$mineraldata["item_to_name"]." : ".$rep_minerals[($mineraldata["item_to_id"]-34)]."<br>\n");
        }
        //판매하려는 양이 구매하려는 양을 넘어서는지 점검한다.(넘어설 경우 판매량에서 차등분 비율을 저장.)
        for($j=0;$j<7;$j++){
            if(max($mineralbuy[$j],0)<$rep_minerals[$j]){
                $mineral_is_ok[$j]=doubleval($rep_minerals[$j]-max($mineralbuy[$j],0))/$rep_minerals[$j];
            }
        }
        
        //팔려는 광의 미네랄량이 구매하는 숫자보다 적으면 (전부 정상적으로 판매되면, $mineral_is_ok 의 원소가 모두 0) 바로 판매를 올린다.
        if(array_sum($mineral_is_ok)==0){
            
            for($j=0;$j<7;$j++){
                $oreprice=$buyprice[0]*$itemstring[$i][1]*$ORE_MAX_BUY_RATE;
            }
            
        }
        //그렇지 않다면 비율을 낮춰야한다.
        else{
            
            $partprice=0;
            for($j=0,$num=0;$j<7;$j++){
                if($rep_minerals[$j]>0 ){
                    
                    $partprice+= $buyprice[0]*(($mineral_is_ok[$j]*$ORE_MIN_BUY_RATE)+((1-$mineral_is_ok[$j])*$ORE_MAX_BUY_RATE));
                    $num++;
                }
                    
            }
            $oreprice=$partprice*$itemstring[$i][1]/$num;
            
            
        }
        for($j=0;$j<7;$j++){
            $mineralbuy[$j]-=$rep_minerals[$j];
            $totalmineral[$j]+=$rep_minerals[$j];
        }
        
        $itemarray[$i_]["typeid"]=$oredata["typeid"];
        $itemarray[$i_]["itemname"]=$oredata["itemname"];
        $itemarray[$i_]["quantity"]=intval($itemstring[$i][1]);
        $itemarray[$i_]["price"]=$oreprice;
        $itemarray[$i_]["buyprice"]=doubleval($buyprice[0])*intval($itemstring[$i][1]);
        $totalvalue+=$oreprice;
        $i_++;
        
        
    }

    //압축 얼음 데이터를 불러온다
    $qr="select * from EVEDB_Item where typeid=\"".$itemstring[$i][0]."\" and compressed=1 and groupid=465;";
    $result=$dbcon->query($qr);
    

    //압축 얼음이면
    if($result->num_rows>0) {

        $rep_ices=array(0,0,0,0,0,0,0);
        $ice_is_ok=array(doubleval(0),doubleval(0),doubleval(0),doubleval(0),doubleval(0),doubleval(0),doubleval(0));

        
        $icedata=$result->fetch_array();
        $qr="select * from Industry_Relation where relation_type=1 and item_from_id=".$icedata["typeid"].";";
        $result=$dbcon->query($qr);

        //바이가를 계산한다.
        $qr="select price from Industry_Marketorders where typeid=".$icedata["typeid"]." and quantity>0 and is_buy_order=1 order by time desc, price desc limit 2;";
        $buypriceresult=$dbcon->query($qr);

        if($buypriceresult->num_rows>0){
            $buyprice=$buypriceresult->fetch_array();
        }
        else{
            $buyprice=[0];
        }
        //판매하려는 압축얼음을 정제했을 때의 ice products를 계산한다.
        for($j=0;$j<$result->num_rows;$j++){
            $productdata=$result->fetch_array();
            $rep_ices[iceproductArrayNum($productdata["item_to_id"])]+=floor($productdata["item_to_quantity"]*$itemstring[$i][1]*$productdata["convert_rate"]);
            //echo("DEBUG : ".$mineraldata["item_from_name"]." x ".$itemstring[$i][1]." -> ".$mineraldata["item_to_name"]." : ".$rep_minerals[($mineraldata["item_to_id"]-34)]."<br>\n");
        }
        //판매하려는 양이 구매하려는 양을 넘어서는지 점검한다.(넘어설 경우 판매량에서 차등분 비율을 저장.)
        for($j=0;$j<7;$j++){
            if(max($iceproductbuy[$j],0)<$rep_ices[$j]){
                $ice_is_ok[$j]=doubleval($rep_ices[$j]-max($iceproductbuy[$j],0))/$rep_ices[$j];
                
            }
        }


        
        //팔려는 ice가 구매하는양보다 적고 모두 부합되면 (전부 정상적으로 판매되면, $mineral_is_ok 의 원소가 모두 0) 바로 판매를 올린다.
        
        if(array_sum($ice_is_ok)==0){
            for($j=0;$j<7;$j++){
                
                $iceprice=$buyprice[0]*$itemstring[$i][1]*$ICE_MAX_BUY_RATE;
            }
        }
        //그렇지 않다면 비율을 낮춰야한다.
        else{
            
            $partprice=0;
            for($j=0,$num=0;$j<7;$j++){
                if($rep_ices[$j]>0 ){
                    
                    $partprice+= $buyprice[0]*(($ice_is_ok[$j]*$ICE_MIN_BUY_RATE)+((1-$ice_is_ok[$j])*$ICE_MAX_BUY_RATE));
                    
                    $num++;
                    
                }
                
            }
            $iceprice=$partprice*$itemstring[$i][1]/$num;
            
            
        }
        for($j=0;$j<7;$j++){
            $iceproductbuy[$j]-=$rep_ices[$j];
            $totalice[$j]+=$rep_ices[$j];
        }
        
        $itemarray[$i_]["typeid"]=$icedata["typeid"];
        $itemarray[$i_]["itemname"]=$icedata["itemname"];
        $itemarray[$i_]["quantity"]=intval($itemstring[$i][1]);
        $itemarray[$i_]["price"]=$iceprice;
        $itemarray[$i_]["buyprice"]=doubleval($buyprice[0])*intval($itemstring[$i][1]);
        $totalvalue+=$iceprice;
        $i_++;
        
        
    }

    //문광 데이터를 불러온다 (groupid : 1884, 1920 ,1921, 1922,1923)
    $qr="select * from EVEDB_Item where typeid=\"".$itemstring[$i][0]."\" and 
    (groupid=1884 or groupid=1920 or groupid=1921 or groupid=1922 or groupid=1923);";
    $result=$dbcon->query($qr);


    //문광이면
    if($result->num_rows>0) {

        
        $moredata=$result->fetch_array();
        
        $qr="select * from Industry_Relation where relation_type=1 and item_from_id=".$moredata["typeid"].";";
        $materialresult=$dbcon->query($qr);
        $more_material=array();
        
        $reprocessed_buyprice=0;
        $more_is_ok=array();
        $mineral_is_ok=array(0,0,0,0,0,0,0);
        $moreprice=0;
        
        //바이가를 계산한다.
        for($j=0;$j<$materialresult->num_rows;$j++){
            $materialdata[$j]=$materialresult->fetch_array();
            $qr="select price from Industry_Marketorders where typeid=".$materialdata[$j]["item_to_id"]." order by time desc, price desc limit 2;";
            $priceresult=$dbcon->query($qr);
            if($priceresult->num_rows>0){
                $materialdata[$j]["buyprice"]=$priceresult->fetch_row();
                $materialdata[$j]["buyprice"]=$materialdata[$j]["buyprice"][0];
            }
            else{
                $materialdata[$j]["buyprice"]=0;
            }

            $more_material[$j]["typeid"]=$materialdata[$j]["item_to_id"];
            $more_material[$j]["quantity"]=intval($itemstring[$i][1]/$materialdata[$j]["item_from_quantity"])*$materialdata[$j]["item_to_quantity"]*$materialdata[$j]["convert_rate"];
            $more_material[$j]["buyprice"]=$materialdata[$j]["buyprice"];
            
            $reprocessed_buyprice+=$more_material[$j]["quantity"]*$more_material[$j]["buyprice"];
            //일반광의 경우 일반광 데이터 쪽에도 저장.
            if($materialdata[$j]["item_to_id"]>=34  && $materialdata[$j]["item_to_id"]<=40){

                $rep_minerals[($materialdata[$j]["item_to_id"]-34)]+=intval($itemstring[$i][1]/$materialdata[$j]["item_from_quantity"])*$materialdata[$j]["item_to_quantity"]*$materialdata[$j]["convert_rate"];

            }


            //구매하려는 양이 없으면 구매희망량 0으로 설정.

                //      일반광 미네랄이 아니다                                      &&     얼음 생산품이 아니다(필요 없는 구문이긴 함)             &&      구매량 정보가 없다.
            if( ( $more_material[$j]["typeid"]<34 || $more_material[$j]["typeid"]>40) &&!in_array($more_material[$j]["typeid"],$ICE_PRODUCTS) && !isset($itembuy[("".$more_material[$j]["typeid"])])){
                $itembuy[("".$more_material[$j]["typeid"])]=0;
                $itembuy_id[sizeof($itembuy_id)]=$more_material[$j]["typeid"];
            }
            $more_is_ok[$j]=0.0;

            //판매하려는 양이 구매하려는 양을 넘어서는지 점검한다.(넘어설 경우 판매량에서 차등분 비율을 저장.)
            //일반 미네랄의 경우
            if($more_material[$j]["typeid"]>=34  && $more_material[$j]["typeid"]<=40){
                if(max($mineralbuy[($more_material[$j]["typeid"]-34)],0)<$rep_minerals[($more_material[$j]["typeid"]-34)]){
                    $mineral_is_ok[($more_material[$j]["typeid"]-34)]=doubleval($rep_minerals[($more_material[$j]["typeid"]-34)]-max($mineralbuy[($more_material[$j]["typeid"]-34)],0))/$rep_minerals[($more_material[$j]["typeid"]-34)];
                    //moon ore is ok 변수도 미네랄과 같이 맞춰준다.
                    $more_is_ok[$j]=$mineral_is_ok[($more_material[$j]["typeid"]-34)];
                }

            }
            //문마테리얼의 경우.
            else if(max($itembuy[("".$more_material[$j]["typeid"])],0)<$more_material[$j]){
                $more_is_ok[$j]=doubleval($more_material[$j]["quantity"]-max($itembuy[("".$more_material[$j]["typeid"])],0) )/$more_material[$j]["quantity"];
                
            }
        }

        //팔려는 Moon Ore가 구매하는양보다 적고 모두 부합되면 (전부 정상적으로 판매되면, $mineral_is_ok 의 원소가 모두 0) 바로 판매를 올린다.
        
        if(array_sum($more_is_ok)==0 && array_sum($mineral_is_ok)){
            for($j=0;$j<sizeof($more_is_ok);$j++){
                //미네랄
                if($more_material[$j]["typeid"]>=34  && $more_material[$j]["typeid"]<=40){
                    $moreprice+=$more_material[$j]["buyprice"]*$more_material[$j]["quantity"]*$MOONORE_MAX_BUY_RATE;
                }
                //문마테리얼
                else{
                    $moreprice+=$more_material[$j]["buyprice"]*$more_material[$j]["quantity"]*$MOONORE_MAX_BUY_RATE;
                }
            }
                
        }
        //그렇지 않다면 비율을 낮춰야한다.
        else{
            $partprice=0;
            $num=0;
            for($j=0;$j<sizeof($more_is_ok);$j++){

                $buyprice=$more_material[$j]["buyprice"]*$more_material[$j]["quantity"];
                $partprice+= $buyprice*(($more_is_ok[$j]*$MOONORE_MIN_BUY_RATE)+((1-$more_is_ok[$j])*$MOONORE_MAX_BUY_RATE));
                
                $num++;
            }
            
            $moreprice=$partprice;
            //errordebug($moreprice);
            
        }
        
        for($j=0;$j<sizeof($more_is_ok);$j++){
            //일반 미네랄
            if($more_material[$j]["typeid"]>=34  && $more_material[$j]["typeid"]<=40){
                $mineralbuy[($more_material[$j]["typeid"]-34)]-=$more_material[$j]["quantity"];
                $totalmineral[($more_material[$j]["typeid"]-34)]+=$more_material[$j]["quantity"];
            }
            //문마테리얼
            else{
                $itembuy[("".$more_material[$j]["typeid"])]-=$more_material[$j]["quantity"];
                $totalitem[("".$more_material[$j]["typeid"])]+=$more_material[$j]["quantity"];
            }
        }
        
        $itemarray[$i_]["typeid"]=$materialdata[0]["item_from_id"];
        $itemarray[$i_]["itemname"]=$materialdata[0]["item_from_name"];
        $itemarray[$i_]["quantity"]=intval($itemstring[$i][1]);
        $itemarray[$i_]["price"]=$moreprice;
        $itemarray[$i_]["buyprice"]=$reprocessed_buyprice;
        $totalvalue+=$moreprice;

        
        $i_++;
        
        
    }


    //PI 물품 데이터를 불러온다
    //Raw (Raw)
    //P1 (Tier1 , Basic) : groupid=1042
    //P2 (Tier2 , Refined) : groupid=1034
    //P3 (Tier3 , Specialized) : groupid=1040 
    //P4 (Tier4 , Advanced) : groupid=1041
    $qr="select * from EVEDB_Item where typeid=\"".$itemstring[$i][0]."\" and 
    (groupid=1034 or groupid=1040 or groupid=1041);";
    $result=$dbcon->query($qr);


    //PI아이템이면
    if($result->num_rows>0) {

        
        $pidata=$result->fetch_array();
        
        $qr="select * from Industry_Relation where relation_type=1 and item_from_id=".$pidata["typeid"].";";
        $materialresult=$dbcon->query($qr);
        $pi_material=array();
        
        $reprocessed_buyprice=0;
        $pi_is_ok=0;
        $piprice=0;

        
        //바이가를 계산한다.
        $qr="select price from Industry_Marketorders where typeid=".$pidata["typeid"]." and is_buy_order=1 and quantity>0 order by time desc, price desc limit 2;";
        $buypriceresult=$dbcon->query($qr);

        if($buypriceresult->num_rows>0){
            $buyprice=$buypriceresult->fetch_array();
        }
        else{
            $buyprice=array(0);
        }

        //판매하려는 양이 구매하려는 양을 넘어서는지 점검한다.(넘어설 경우 판매량에서 차등분 비율을 저장.)
        
        //판매량이 더 크면
        if((isset($itembuy["".$pidata["typeid"]])?max($itembuy["".$pidata["typeid"]],0):0)<$itemstring[$i][1]) {
            $pi_is_ok=doubleval($itemstring[$i][1]-max($itembuy["".$pidata["typeid"]],0))/$itemstring[$i][1];
        }
        
        
        //판매량이 더 적으면 (전부 고가판매 처리되면, $pi_is_ok 의 원소가 모두 0) 바로 판매를 올린다.
        if($pi_is_ok==0){
            switch($pidata["groupid"]) {
                case 1034:  //P2
                    $piprice=$buyprice[0]*$itemstring[$i][1]*$PI_P2_MAX_BUY_RATE;
                break;
                case 1040:  //P3
                    $piprice=$buyprice[0]*$itemstring[$i][1]*$PI_P3_MAX_BUY_RATE;
                break;
                case 1041:  //P4
                    $piprice=$buyprice[0]*$itemstring[$i][1]*$PI_P4_MAX_BUY_RATE;
                break;
            }
        }
        //그렇지 않다면 비율을 낮춰야한다.
        else{
            errorlog($pidata["groupid"]);
            switch($pidata["groupid"]) {
                case 1034:  //P2
                    $piprice=$buyprice[0]*$itemstring[$i][1]*$PI_P2_MIN_BUY_RATE*$pi_is_ok + $buyprice[0]*$itemstring[$i][1]*$PI_P2_MAX_BUY_RATE*(1-$pi_is_ok);
                break;
                case 1040:  //P3
                    $piprice=$buyprice[0]*$itemstring[$i][1]*$PI_P3_MIN_BUY_RATE*$pi_is_ok + $buyprice[0]*$itemstring[$i][1]*$PI_P3_MAX_BUY_RATE*(1-$pi_is_ok);
                break;
                case 1041:  //P4
                    $piprice=$buyprice[0]*$itemstring[$i][1]*$PI_P4_MIN_BUY_RATE*$pi_is_ok + $buyprice[0]*$itemstring[$i][1]*$PI_P4_MAX_BUY_RATE*(1-$pi_is_ok);
                break;
            }
        }
        
        $itemarray[$i_]["typeid"]=$pidata["typeid"];
        $itemarray[$i_]["itemname"]=$pidata["itemname"];
        $itemarray[$i_]["quantity"]=intval($itemstring[$i][1]);
        $itemarray[$i_]["price"]=$piprice;
        $itemarray[$i_]["buyprice"]=$buyprice[0]*intval($itemstring[$i][1]);
        $totalvalue+=$piprice;

        
        $i_++;
        
        
    }

    //기타 물품 데이터를 불러온다
    //Salvaged Materials : groupid=754
    //Datacore : groupid=333
    //Decryptor : groupid=1304
    //Materials and Compounds : groupid=530
    //Comodities : groupid=526
    //Rogue Drone Components : groupid=886
    //Miscellaneous : groupid=314
    //General : groupid=280
    //Ancient Salvage : groupid=966
    //Artifacts and Prototypes : 528
    //Construction Components : 334



    //Decryptors - Amarr : groupid=728
    //Decryptors - Minmatar : groupid=729
    //Decryptors - Gallente : groupid=730
    //Decryptors - Caldari : groupid=731
    //Decryptors - Sleeper : groupid=732
    //Decryptors - Yan Jung : groupid=733
    //Decryptors-Takmahl : groupid=734
    //Decryptors-Talocan : groupid=735
    
    //Abyssal Filaments : groupid=1979
    //Jump Filaments : groupid=4041
    //Triglavian Space Filaments : groupid=4087
    $qr="select * from EVEDB_Item where typeid=\"".$itemstring[$i][0]."\" and 
    (groupid=754 or 
    groupid=333 or
    groupid=1304 or 
    groupid=530 or 
    groupid=526 or 
    groupid=886 or 
    groupid=314 or 
    groupid=280 or 
    groupid=966 or 
    groupid=528 or 
    groupid=334 or
    (groupid>727 and groupid<736) or
    groupid=1979 or
    groupid=4041 or
    groupid=4087
    );";
    $result=$dbcon->query($qr);


    //기타 물품이면
    if($result->num_rows>0) {

        
        $expdata=$result->fetch_array();
        
        $qr="select * from Industry_Relation where relation_type=1 and item_from_id=".$expdata["typeid"].";";
        $materialresult=$dbcon->query($qr);
        $exp_material=array();
        
        $reprocessed_buyprice=0;
        $exp_is_ok=0;
        $expprice=0;

        
        //바이가를 계산한다.
        $qr="select price from Industry_Marketorders where typeid=".$expdata["typeid"]." and is_buy_order=1 and quantity>0 order by time desc, price desc limit 2;";
        $buypriceresult=$dbcon->query($qr);

        if($buypriceresult->num_rows>0){
            $buyprice=$buypriceresult->fetch_array();
        }
        else{
            $buyprice=array(0);
        }

        //판매하려는 양이 구매하려는 양을 넘어서는지 점검한다.(넘어설 경우 판매량에서 차등분 비율을 저장.)
        
        //판매량이 더 크면
        if((isset($itembuy["".$expdata["typeid"]])?max($itembuy["".$expdata["typeid"]],0):0)<$itemstring[$i][1]) {
            $exp_is_ok=doubleval($itemstring[$i][1]-max($itembuy["".$expdata["typeid"]],0))/$itemstring[$i][1];
        }
        
        
        //판매량이 더 적으면 (전부 고가판매 처리되면, $exp_is_ok 의 원소가 모두 0) 바로 판매를 올린다.
        if($exp_is_ok==0){

            $expprice=$buyprice[0]*$itemstring[$i][1]*$EXPLORER_MATERIAL_MAX_BUY_RATE;

        }
        //그렇지 않다면 비율을 낮춰야한다.
        else{
            errorlog($expdata["groupid"]);

            $expprice=$buyprice[0]*$itemstring[$i][1]*$EXPLORER_MATERIAL_MIN_BUY_RATE*$exp_is_ok + $buyprice[0]*$itemstring[$i][1]*$EXPLORER_MATERIAL_MAX_BUY_RATE*(1-$exp_is_ok);

        }
        
        $itemarray[$i_]["typeid"]=$expdata["typeid"];
        $itemarray[$i_]["itemname"]=$expdata["itemname"];
        $itemarray[$i_]["quantity"]=intval($itemstring[$i][1]);
        $itemarray[$i_]["price"]=$expprice;
        $itemarray[$i_]["buyprice"]=$buyprice[0]*intval($itemstring[$i][1]);
        $totalvalue+=$expprice;

        $i_++;
            
    }

}
if(isset($_POST["itemlist"]) && sizeof($itemarray)>0 && $_POST["username"]!="" && !isset($_POST["submit"])) {
    echo("<span id=\"contractdata\">");
    echo("<a href=\"javascript:SubmitContract();\" class=\"apply\">컨트랙 신청하기</a><br><br>");
    echo("신청을 한 후, 같은 내용으로 인게임 컨트랙을 거시면 됩니다.<br>\n");
    echo("문광 가격은 정제품 기준 가격입니다.<br>\n");
    echo("신청인 : ".$_POST["username"]."<br>\n");
    echo("<span class=\"totalprice\">Total : ".number_format(floor($totalvalue))." ISK</span><br>\n");
    echo("<table>\n");
    echo("<tr><th>아이템</th><th>수량</th><th>매입가</th><th>지타바이가</th><th>바이백비율</th></tr>");
    for($i=0;$i<sizeof($itemarray);$i++){

        
        echo("<tr>\n");
        //echo("<td>".$itemarray[$i]["typeid"]."</td>\n");
        echo("<td>".$itemarray[$i]["itemname"]."</td>\n");
        echo("<td>".$itemarray[$i]["quantity"]."</td>\n");
        echo("<td>".number_format($itemarray[$i]["price"],2)."</td>\n");
        echo("<td>".number_format($itemarray[$i]["buyprice"],2)."</td>\n");
        echo("<td>".number_format(($itemarray[$i]["price"]/$itemarray[$i]["buyprice"])*100,1)." %</td>\n");
        echo("</tr>\n");
    }
    echo("</table><br>\n");
    echo("<script>\n");
    echo("var SendingStuffs=[");
    for($i=0;$i<sizeof($itemarray);$i++){
        echo("[".$itemarray[$i]["typeid"].",".$itemarray[$i]["quantity"]."]");
        if($i!=sizeof($itemarray)-1){
            echo(",");
        }
    }
    echo("];\n");
    echo("var totalvalue=".(floor($totalvalue*100)/100).";\n");
    echo("var username=\"".$_POST["username"]."\";\n");
    echo("</script>\n");

    echo("<span class=\"totalprice\">Total : ".number_format($totalvalue)." ISK</span><br>\n");
    echo("<a href=\"javascript:SubmitContract();\" class=\"apply\">컨트랙 신청하기</a><br><br>");
    echo("</span>");
}
else if (isset($_POST["submit"]) && $_POST["submit"]==1 && $totalvalue-$_POST["total"]<1 && $totalvalue-$_POST["total"]>-1){


    for($i=0;$i<7;$i++){
        $dbcon->query("update Industry_Contracts set quantity=quantity-".$totalmineral[$i]." where typeid=".($i+34)." and contract_id=1");
        $dbcon->query("update Industry_Contracts set quantity=quantity-".$totalice[$i]." where typeid=".$ICE_PRODUCTS[$i]." and contract_id=1");
    }
    for($i=0;$i<sizeof($itembuy_id);$i++){
        $dbcon->query("update Industry_Contracts set quantity=quantity-".$totalitem["".$itembuy_id[$i]]." where typeid=".$itembuy_id[$i]." and contract_id=1");
    }

    $contn=$dbcon->query("select contract_id from Industry_Contracts order by contract_id desc limit 1")->fetch_array();
    $contn=($contn[0]+1);
    for($i=0;$i<sizeof($itemarray);$i++){
        $qr="insert into Industry_Contracts (contract_id,typeid,itemname,username,quantity,price,buyprice) values(".$contn.",".$itemarray[$i]["typeid"].",\"".$itemarray[$i]["itemname"]."\",\"".$_POST["username"]."\",".$itemarray[$i]["quantity"].",".(floor($itemarray[$i]["price"]*100)/100).",".(floor($itemarray[$i]["buyprice"]*100)/100).");";
        $dbcon->query($qr);
    }
}
else if($_POST["username"]=="" ){

    echo("캐릭터 이름을 입력해주세요.<br>\n");
}

function iceproductArrayNum( $ice_product_id){

    switch($ice_product_id){
        //Heavy Water
        case 16272:
            return 0;
        break;
        //Liquid Ozone
        case 16273:
            return 1;
        break;
        //Strontium Clathrates
        case 16275:
            return 2;
        break;
        //Helium Isotopes
        case 16274:
            return 3;
        break;
        //Oxygen Isotopes
        case 17887:
            return 4;
        break;
        //Nitroen Isotopes
        case 17888:
            return 5;
        break;
        //Hydrogen Isotopes
        case 17889:
            return 6;
        break;
    }

}

?>

<script language="javascript">

function SubmitContract(){
    
    var XHR = new XMLHttpRequest();
    var senddata="submit=1&total="+totalvalue+"&username="+username+"&json="+JSON.stringify(SendingStuffs);
    XHR.onreadystatechange = function() {
        
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("contractdata").innerHTML="신청 완료. 창을 닫으셔도 됩니다.<br>";
        }

    };
    
    XHR.open('POST', "./SubmitContract.php", true);
    //XHR.setRequestHeader("Content-Type", "application/json");
    XHR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;");
    XHR.send(senddata);
    
}

</script>