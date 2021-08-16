<?php
    require('phpQuery/phpQuery.php');
    require('./curl-helper.php');
    try{
        echo "Curling started";
        $allFaceToFaceData = [];
        function getArrayOfPracticeAreaData($array){
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
                $request = new CurlHelper($newUrl,"GET");
                $paginateData = $request->makeRequest();
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
                    $html[] =[$practice_name,pq($rs)->find('strong.name')->text(),$phone,$street,$city,$state,$postCode,$country,$funding_schema,$area_practice]; 
                }
            }
            
            return $html;
        }

        function getPaginatedData($serviceId,$areaPractice){
            $arrayOfId = [];
            $postFields =[
                'ServiceType'=>$serviceId,
                'AreaOfPracticeId'=>$areaPractice
            ];
            $request = new CurlHelper("https://www.otaus.com.au/search/membersearchdistance","POST",$postFields);
            $getMemberSearchDistance= $request->makeRequest();
            $ids = json_decode($getMemberSearchDistance,true);
            $arrayOfId = getArrayOfPracticeAreaData($ids['mainlist']);
            return $arrayOfId;
        }

        $request = new CurlHelper("https://www.otaus.com.au/find-an-ot","GET");
        $serviceProvidedBy=null;
        $getFacetoFaceAreaOfPractice = $request->makeRequest();
        $doc = phpQuery::newDocument($getFacetoFaceAreaOfPractice);
        phpQuery::selectDocument($doc);
        foreach (pq('#memberSearch_ServiceType > option') as $opt) {
            if($opt->textContent=='Face-to-face'){
            $serviceProvidedBy = $opt->getAttribute('value');
            }
        }
        if($serviceProvidedBy!=null){
            foreach(pq('#memberSearch_AreaOfPracticeId > option') as $k=>$opt){
                if($opt->getAttribute('value')!='' && $opt->getAttribute('value')!=null){
                    if($k==1){
                        if($serviceProvidedBy){
                            $allFaceToFaceData[] =(object)['name'=>$opt->textContent,'data'=>getPaginatedData($serviceProvidedBy,$opt->getAttribute('value'))];
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
        echo "\nCurling Completed";
    }catch(Exception $e){
        echo "Something went wrong!";
    }
   
?>