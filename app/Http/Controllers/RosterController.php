<?php

namespace App\Http\Controllers;
use \DOMDocument;
use Response;
use \Illuminate\Http\Response as Res;
class RosterController extends Controller
{
    public function RosterFlightData()
    {
    	//I saved file in public folder
    	//Instead this can use upload toread data
    	$file_name = "EXAMPLEROSTER.html";
		$file_url = 'public\html\\'. $file_name;
		$content = file_get_contents(base_path($file_url));
		//This function will make the json
		$data = $this->html_to_obj($content);

		return $data;
    }
    
    function html_to_obj($html) {
	  
	    $htmlContent = $html;
		//Load the html
		$DOM = new DOMDocument();
		libxml_use_internal_errors(true);
		$DOM->loadHTML($htmlContent);
		//Read Tr and Td
		$Header = $DOM->getElementsByTagName('tr');
		$Detail = $DOM->getElementsByTagName('td');
		$aDataTableHeaderHTML=array();
		$TableRow=array();

	    //#Get header name of the table	  
		foreach($Header as $key=>$NodeHeader) 
		{			
			$TableRow[$key] = $this->tdrows($NodeHeader->childNodes);
		}	
		//Read the table header
		$Dayheader = $TableRow[5];	

		$startPointer=6;
		$endPointer=34;
		$returnData=array();
		//Make a FLight List
		$flightList=['8751','8752','8462','8465','8113','8114','8277','8274','8222','8221','8973','8974','8233','8234','807','808','837','840','8551','8552','8187','8186','8985','8986','8663','8664','8881','8884','891','892','841','842','8601','8602'];
		//Make alist of notifications
		$arrNotification=['d/o'=>'Day Off','esby'=>'Early Standby','csbe'=>'Crewing Standby Early','adty'=>'Airport Duty on Standby','intv'=>'Interviews / Interviewing'];
		//Foreach header (day) get the flight details
		foreach ($Dayheader as $key => $value) {
			$tdData=array();
			
			for($i=$startPointer;$i<=$endPointer;$i++)
			{
				//Check if date has day of or any notification
				//Since if there is notification there wouldn't be any flight so break the loop
				if(array_key_exists(strtolower($TableRow[$i][$key]), $arrNotification))					
				{
					array_push($tdData ,$arrNotification[strtolower($TableRow[$i][$key])]);
					break;					
				}
				else
				{	
					//Get the flight details for each day					
					array_push($tdData, $TableRow[$i][$key]);
				}											
			}	
			
			if(count($tdData)>2)
			{
				//since flight pattern have 6 param
				//chunk the data into fixedlenght(6)
				$td_sliced = array_chunk($tdData, 6);
				$fligtModal=array();//Array to keep flight modal
				//Loop each flight modal
				foreach ($td_sliced as $index => $tdvalue) 
				{
					//IF flights running on that day
					if(count($tdvalue)>5)
					{
						//if first value is empty then first shift the array and then insert empty to last index
						//to make all array in equal lenght
						if(empty($tdvalue[0]))
						{
							array_shift($tdvalue);
							array_push($tdvalue, '');
						}
						//check if flight exist in first index
						if(in_array($tdvalue[0], $flightList))
						{
							//make flight modal
							if($index==0)
							{							
								array_push($fligtModal ,array(
								'Flight Number'=>$tdvalue[0],
								'Report Time'=>$tdvalue[1],
								'Departure Time'=>$tdvalue[2],
								'Departure Airport'=>$tdvalue[3],							
								'Arrival Time'=>$tdvalue[5],
								'Arrival Airport'=>$tdvalue[4],
								));
								$nextFlightReportTime=$tdvalue[5];	 
								//Report time of next flight is arrival time of first flight
							}
							else
							{
								array_push($fligtModal ,array(
									'Flight Number'=>$tdvalue[0],
									'Report Time'=>$nextFlightReportTime,
									'Departure Time'=>$tdvalue[1],
									'Departure Airport'=>$tdvalue[2],								
									'Arrival Time'=>$tdvalue[4],
									'Arrival Airport'=>$tdvalue[3],
								));	
								//Report time of next flight is arrival time of first flight
								$nextFlightReportTime=$tdvalue[4];	
							}	
						}

					}
										
				}
				
				array_push($returnData,array($value=>$fligtModal));
			}
			else
				array_push($returnData,array($value=>$tdData));	
			
			
		}
		//Make json data and return
		$response = response()->json([			
			'data' =>$returnData,
			'error'=>array()
			],RES::HTTP_OK);
		
		return $response;
		
		

	}
	 function tdrows($elements) 
	{ 
	    $str = ""; 
	    $RowData=[];
	    //Read the html row and make an row
	    foreach ($elements as $i=>$element) { 
	    	$nodeValue = trim($element->nodeValue);
	    	
	    	if(!empty($nodeValue))
	    		$RowData[$i]= preg_replace('/\xc2\xa0/', '', $nodeValue);
	        
	    } 	  

	    return $RowData; 
	}
		
}
