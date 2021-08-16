<?php
    require('phpQuery/phpQuery.php');

    $allFaceToFaceData = [];
    $curl = curl_init();
    if (!$curl) {
        die("Couldn't initialize a cURL handle");
    }
   
    curl_setopt($curl,CURLOPT_URL,"https://www.otaus.com.au/find-an-ot");
    curl_setopt($curl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 50);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $html = curl_exec($curl);
    if (curl_errno($curl))
    {
        echo 'cURL error: ' . curl_error($curl);
    }
    else
    {
        curl_close($curl);
        // cURL executed successfully
        $serviceProvidedBy=null;
         $doc = phpQuery::newDocument($html);
         phpQuery::selectDocument($doc);
         foreach (pq('#memberSearch_ServiceType > option') as $opt) {
             if($opt->textContent=='Face-to-face'){
                $serviceProvidedBy = $opt->getAttribute('value');
             }
         }
         foreach(pq('#memberSearch_AreaOfPracticeId > option') as $k=>$opt){
             if($opt->getAttribute('value')!='' && $opt->getAttribute('value')!=null){
                 
                 if($k==1){
                     if($serviceProvidedBy){
                         $allFaceToFaceData[] =(object)['name'=>$opt->textContent,'data'=>postAllData($serviceProvidedBy,$opt->getAttribute('value'))];
                     }
                 }
             }
            
         }
       
    }
    $file = fopen('data.csv','w');
    foreach($allFaceToFaceData as $data){
        foreach($data->data as $row){    
            fputcsv($file,$row);
        }
    }
    fclose($file);
    
   
    
    echo json_encode($allFaceToFaceData);

    function postAllData($serviceId,$areaPractice){
        $newcurl = curl_init();
        if (!$newcurl) {
            die("Couldn't initialize a cURL handle");
        }
        $postFields =[
            'ServiceType'=>$serviceId,
            'AreaOfPracticeId'=>$areaPractice
        ];
        curl_setopt($newcurl,CURLOPT_URL,"https://www.otaus.com.au/search/membersearchdistance");
        curl_setopt($newcurl, CURLOPT_POSTFIELDS, $postFields);

        curl_setopt($newcurl, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
        curl_setopt($newcurl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($newcurl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($newcurl, CURLOPT_TIMEOUT, 50);
        curl_setopt($newcurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($newcurl, CURLOPT_SSL_VERIFYPEER, false);
        $ids = curl_exec($newcurl);
        $arrayOfId = [];
        if (curl_errno($newcurl))
        {
            echo 'cURL error: ' . curl_error($newcurl);
        }else{
           curl_close($newcurl);
           $ids = json_decode($ids,true);
           $arrayOfId = makeData($ids['mainlist']);
        }
        return $arrayOfId;
    }

    function makeData($array){
       
        $newArr = array_chunk($array,10);
        $urls=[];
        foreach($newArr as $k=>$chunkArray){
            $url = "https://www.otaus.com.au/search/getcontacts?";
            foreach($chunkArray as $k=>$result){
                if($k==0)
                    $url .= "ids=".$result;
                else
                    $url .= "&ids=".$result;
                break;
            }
            $urls[] = $url;
        }
        $html=[];
        foreach($urls as $newUrl){
            $realData = curl_init();
            if (!$realData) {
                die("Couldn't initialize a cURL handle");
            }
            curl_setopt($realData,CURLOPT_URL,$newUrl);
            curl_setopt($realData, CURLOPT_USERAGENT, 'Googlebot/2.1 (+http://www.google.com/bot.html)');
            curl_setopt($realData,CURLOPT_RETURNTRANSFER,true);
            curl_setopt($realData, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($realData, CURLOPT_TIMEOUT, 50);
            curl_setopt($realData, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($realData, CURLOPT_SSL_VERIFYPEER, false);
            $paginateData = curl_exec($realData);
            curl_close($realData);
            $doc = phpQuery::newDocument($paginateData);
            phpQuery::selectDocument($doc);
            foreach(pq('.results__item') as $rs){
                $practice_name = pq($rs)->find('.title__tag')->text();
                $phone = "";
                $street="";
                $city = "";
                $state = "";
                $postCode="";
                $country="";
                $funding_schema="";
                $area_practice ="";
                $funding_area="";
                foreach(pq($rs)->find('p') as $k=>$paragraph){
                    if($k==1){
                        $address = preg_split('/<br[^>]*>/i', pq($paragraph));
                        if(array_key_exists(0,$address))
                            $street =trim(preg_replace('/\s+/', '', strip_tags($address[0])));
                        if(array_key_exists(1,$address)){
                            $cityStatePostCode = explode(",",$address[1]);
                            if(array_key_exists(0,$cityStatePostCode))
                                $city = trim(preg_replace('/\s+/', '', strip_tags($cityStatePostCode[0])));
                            if(array_key_exists(1,$cityStatePostCode))
                                $state =trim(preg_replace('/\s+/', '', strip_tags($cityStatePostCode[1])));
                            if(array_key_exists(2,$cityStatePostCode))
                                $postCode =trim(preg_replace('/\s+/', '',  strip_tags($cityStatePostCode[2])));
                        }
                        if(array_key_exists(2,$address))
                            $country= trim(preg_replace('/\s+/', '',  strip_tags($address[2])));
                    }
                    if($k==2){
                        $phone =trim(preg_replace('/\s+/', '', strip_tags(pq($paragraph)->find('a')->text())));
                        
                    }
                    if($k==7){
                        $funding_area = preg_split('/<br[^>]*>/i', pq($paragraph));
                        array_pop($funding_area);
                        foreach($funding_area as $fs_ap){
                            
                            $title_value=explode(':',$fs_ap);
                            if(array_key_exists(0,$title_value)){
                                if(strpos(strip_tags($title_value[0]),"Funding Scheme(s)")!==false){
                                     
                                    $funding_schema = trim(preg_replace('/\s+/', '', strip_tags($title_value[1])));
                                }
                                if(strpos(strip_tags($title_value[0]),'Area(s) of Practice')!== false){
                                    $area_practice= trim(preg_replace('/\s+/', '',strip_tags($title_value[1])));
                                }
                                
                            }
                        }
                        
                       
                    }
                }
                // $html[] = (object)['contact_name'=>pq($rs)->find('strong.name')->text(),'phone'=>$phone,'street'=>$street,'city'=>$city,'state'=>$state,'postCode'=>$postCode,'country'=>$country,'funding_schema'=>$funding_schema,'area_practice'=>$area_practice];
                $html[] =[$practice_name,pq($rs)->find('strong.name')->text(),$phone,$street,$city,$state,$postCode,$country,$funding_schema,$area_practice]; 
            }
           
        }
        return $html;
    }
   
?>