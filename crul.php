<?php
    require('phpQuery/phpQuery.php');
    require('./curl-helper.php');
    try{
        echo "Curling started";
        $allFaceToFaceData = [];
        //return all parsed result item for each practice area
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
                }
                $urls[] = $url;
            }
            foreach($urls as $newUrl){
                $request = new CurlHelper($newUrl,"GET");
                $paginateData = $request->makeRequest();
                $doc = phpQuery::newDocument($paginateData);
                //Each Result Item DIV
                phpQuery::selectDocument($doc);
                foreach(pq('.results__item') as $rs){
                    $phone = "";
                    $street="";
                    $city = "";
                    $state = "";
                    $postCode="";
                    $country="";
                    $funding_schema="";
                    $area_practice ="";
                    $funding_area="";
                    //Get First Content Row For Each Result Item Div
                    $contentRow = pq($rs)->find('div.content__row:first');
                    $practice_name = pq($contentRow)->find('.title__tag')->text();
                    $contact_name = pq($contentRow)->find('strong.name')->text();
                    foreach(pq($contentRow)->find('p') as $k=>$paragraph){
                        if($k==1){
                            if(count(pq($paragraph)->find('br'))){
                                //Parse Address 
                                $address = preg_split('/<br[^>]*>/i', pq($paragraph));
                                if(array_key_exists(0,$address))
                                    $street =trim(preg_replace('/\s+/', '', strip_tags($address[0])));
                                if(array_key_exists(1,$address)){
                                    preg_match('(\d{4})',strip_tags($address[1]),$postcode);
                                    if(array_key_exists(0,$postcode))
                                        $postCode = $postcode[0];
                                    preg_match('([A-Z]{2,3})',strip_tags($address[1]),$stateMatch);
                                    if(array_key_exists(0,$stateMatch))
                                        $state = $stateMatch[0];
                                    preg_match('/([^,]+)/',strip_tags($address[1]),$cityMatch);
                                    if(array_key_exists(0,$cityMatch))
                                        $city = trim(preg_replace('/\s+/', '',  strip_tags($cityMatch[0])));
                                }
                                if(array_key_exists(2,$address))
                                    $country= trim(preg_replace('/\s+/', '',  strip_tags($address[2])));
                            }
                        }
                        //Parse Phone No
                        if(pq($paragraph)->find('.contact-heading')->text()=="Phone:"){
                            $phone =trim(preg_replace('/\s+/', '', strip_tags(pq($paragraph)->find('a')->text())));
                        }
                        //Parse Funding Scheme & Area Of Practice
                        if($k==(count(pq($contentRow)->find('p'))-1)){
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
                    
                    //return parsed data
                    $html[] =[$practice_name,$contact_name,$phone,$street,$city,$state,$postCode,$country,$funding_schema,$area_practice]; 
                }
            }
            
            return $html;
        }
        //return all parsed for each practice area
        function getPaginatedData($serviceId,$areaPractice){
            $arrayOfId = [];
            $postFields =[
                'ServiceType'=>$serviceId,
                'AreaOfPracticeId'=>$areaPractice
            ];
            $request = new CurlHelper("https://www.otaus.com.au/search/membersearchdistance","POST",$postFields);
            $getMemberSearchDistance= $request->makeRequest();
            $ids = json_decode($getMemberSearchDistance,true);
            //Get All Paginated Data For Each Practice Id
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
                        if($serviceProvidedBy){
                            //Get Data For Each Practice Area
                            $allFaceToFaceData[] =(object)['data'=>getPaginatedData($serviceProvidedBy,$opt->getAttribute('value'))];
                        }   
                }
            }
        }
        
        ##For write to csv file
        $file = fopen('data.csv','w');
        fputcsv($file,['Practice Name','Contact Name','Phone','Street','City','State','Postal Code','Country','Funding Schema','Area(s) of Practice']);
        foreach($allFaceToFaceData as $k=>$data){
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