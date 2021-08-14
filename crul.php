<?php
    require('phpQuery/phpQuery.php');
    function makeData($array){
       
        $newArr = array_chunk($array,10);
        $urls=[];
        foreach($newArr as $chunkArray){
            $url = "https://www.otaus.com.au/search/getcontacts?";
            foreach($chunkArray as $k=>$result){
                if($k==0)
                    $url .= "ids=".$result;
                else
                    $url .= "&ids=".$result;
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
                $html[] = curl_exec($realData);
            curl_close($realData);
        }
        return $html;
    }


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
           $arrayOfId [] = makeData($ids['mainlist']);
        }
        return $arrayOfId;
    }
    
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
         
         $areaOfPractice=[];
         foreach(pq('#memberSearch_AreaOfPracticeId > option') as $k=>$opt){
             if($opt->getAttribute('value')!='' && $opt->getAttribute('value')!=null)
            //  $areaOfPractice[] = (object)['name'=>$opt->textContent,'value'=>$opt->getAttribute('value')];
            if($k==1){
                if($serviceProvidedBy){
                    $allFaceToFaceData[] =postAllData($serviceProvidedBy,$opt->getAttribute('value'));
                }
            }
         }
       
    }
   
    
      print_r($allFaceToFaceData);
   
?>