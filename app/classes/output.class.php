<?php
class output {
	public $results;
	public $againstworktime;
	public $asworktimes;
	public $periods;
	public $arrays;
	
	public $xmlOutput;
	public $jsonOutput;

	function __construct(){
		$this->results = array();
		$this->againstworktime = array();
		$this->asworktimes = array();
		$this->periods = array();
		$this->stats = array();
		$this->arrays = array();
		$this->xmlOutput = "";
		$this->jsonOutput = "";
	}
	
	public function outputToXml(){
		$this->xmlIntro();
		foreach($this->results as $task => $result_arr){
			$this->resultXMLoutput($result_arr, $task);
		}
		foreach($this->arrays as $task => $array_arr){
			$this->arrayXMLoutput($array_arr, $task);
		}
		foreach($this->periods as $periods_arr){
			$this->periodsXMLoutput($periods_arr);
		}
		foreach($this->stats as $task => $stats_arr){
			$this->xmlOutput .= "\t<stats>\n";
			$this->statsXMLoutput($stats_arr, $task);
			//$this->arrayXMLoutput($stats_arr, $task);
			$this->xmlOutput .= "\t</stats>\n";
		}
		foreach($this->againstworktime as $againstworktime_arr){
			$this->againstworktimeXMLoutput($againstworktime_arr);
		}
		foreach($this->asworktimes as $asworktimes_arr){
			$this->asworktimeXMLoutput($asworktimes_arr);
		}
		$this->xmlOutro();
		
		return $this->xmlOutput;
			
	}
	
	public function outputToJson(){
		$output = array();
		if(!empty($this->results)) $output["results"] = $this->results; 
		if(!empty($this->periods)) $output["periods"] = $this->periods; 
		if(!empty($this->stats)) $output["stats"] = $this->stats; 
		if(!empty($this->againstworktime)) $output["againstworktime"] = $this->againstworktime; 
		if(!empty($this->asworktimes)) $output["asworktimes"] = $this->asworktimes; 
		if(!empty($this->arrays)) $output["arrays"] = $this->arrays; 
		$this->jsonOutput = json_encode($output);
		return  $this->jsonOutput;
	}
	
	
	/**
	*	XML-formating helper functions
	*/
	
	private function xmlIntro() {
		$this->xmlOutput = "";
		$this->xmlOutput .= "<udtime>\n";
	}
	
	private function xmlOutro() {
		$this->xmlOutput .= "</udtime>\n";
	}

	
	/***
	** Results array where key: 0 is 1 or 0 for success or not, rest of the array are messages
	*/
	
	private function resultXMLoutput($result_arr, $task) {
		$local_xml_output = "";
		if($result_arr[0]) $local_xml_output .= "\t<result success=\"true\" action=\"$task\">\n";
		else $local_xml_output .= "\t<result success=\"false\" action=\"$task\">\n";
		unset($result_arr[0]);
		foreach ($result_arr as $s) {
			$local_xml_output .= "\t\t<message>".$s."</message>\n";
		} 
		$local_xml_output .= "\t</result>\n";
		//header('Content-type: text/xml'); 
		$this->xmlOutput .= $local_xml_output;
	}
	
	/***
	** Use add arbitrary array with mainkey as distinguisher
	*/
	
	/*private function arrayXMLoutput($rarray, $mainkey) {
		$local_xml_output = "";
		$local_xml_output .= "\t<{$mainkey}>\n";
		foreach (array_keys($rarray) as $key) {
			if(is_array($rarray[$key][0])) {
				foreach ($rarray[$key] as $innerarray) {
					$local_xml_output .= "\t<{$key}>\n";
					foreach(array_keys($innerarray) as $innerkeys){
						$local_xml_output .= "\t\t<$innerkeys>".$innerarray[$innerkeys]."</$innerkeys>\n";
					}
					$local_xml_output .= "\t</{$key}>\n";
				}
			}
			else $local_xml_output .= "\t\t<$key>".$rarray[$key]."</$key>\n";
		} 
		$local_xml_output .= "\t</{$mainkey}>\n";
		$this->xmlOutput .=  $local_xml_output;
	}*/
	
	function arrayXMLoutput1($rarray, $mainkey) {
		$local_xml_output = "";
		$local_xml_output .= "\t<{$mainkey}>\n";
		foreach (array_keys($rarray) as $key) {
			if(is_array($rarray[$key])) $local_xml_output .= $this->arrayXMLoutputInner($rarray[$key],$key);
			else $local_xml_output .= "\t\t<$key>".$rarray[$key]."</$key>\n";
		} 
		$local_xml_output .= "\t</{$mainkey}>\n";
		$this->xmlOutput .=  $local_xml_output;
	}
	
	function arrayXMLoutputInner($array,$mainkey){
		//echo "On inner with mainkey: {$mainkey}";
		$local_xml_output = "";
		$local_xml_output .= "\t<{$mainkey}>\n"; 
		foreach (array_keys($array) as $key) {
			if(is_array($array[$key])) $local_xml_output .= $this->arrayXMLoutputInner($array[$key],$key);
			else $local_xml_output .= "\t\t<$key>".$array[$key]."</$key>\n";
		}
		$local_xml_output .= "\t</{$mainkey}>\n";
		return $local_xml_output;
	}
	
	function arrayXMLoutput($rarray, $mainkey) {
		$xml = Array2XML::createXML($mainkey, $rarray);
		$this->xmlOutput .= $xml->saveHTML();
	}
	
	/***
	** 
	*/
	
	private function statsXMLoutput($rarray, $mainkey) {
		$local_xml_output = "";
		$local_xml_output .= "\t<stats>\n";
		foreach ($rarray as $speriod) {
		$local_xml_output .= "\t\t<{$mainkey}>\n";
			foreach (array_keys($speriod) as $key) {
				$local_xml_output .= "\t\t\t<$key>".$speriod[$key]."</$key>\n";
			} 
		$local_xml_output .= "\t\t</{$mainkey}>\n";
		}
		$local_xml_output .= "\t</stats>\n";
		$this->xmlOutput .=  $local_xml_output;
	}
	
	/***
	** Use fetchPeriodsArray($timearray) to get array
	*/
	
	private function periodsXMLoutput($rarray) {
	$local_xml_output = "";
	if (count($rarray) == 0) return;		// Quit if array empty
		foreach($rarray as $period){
			if(count($period) > 1){
				$local_xml_output .= "\t<period>\n";
				foreach (array_keys($period[0]) as $key) {
					$local_xml_output .= "\t\t<$key>".$period[0][$key]."</$key>\n";
				} 
				unset($period[0]);		
				foreach($period as $break){
					$local_xml_output .= $this->returnArrayXMLoutput($break,"break");
				}
				$local_xml_output .= "\t</period>\n";
			}else{
				$local_xml_output .= $this->returnArrayXMLoutput($period[0],"period");
			}
		}
		$this->xmlOutput .= $local_xml_output;
	}
	
	/***
	** Use $timespan->getagainstworktime() to get array
	*/
	
	private function againstworktimeXMLoutput($againstworktime) {
		$local_xml_output = "";
		if(count($againstworktime['days']) > 0){
			foreach (array_keys($againstworktime['days']) as $date) {
				$local_xml_output .= "\t<againstworktime>\n";
				$local_xml_output .= "\t\t<id>".$againstworktime['days'][$date]['id']."</id>\n";
				$local_xml_output .= "\t\t<date>".$date."</date>\n";		
				$local_xml_output .= "\t\t<time>".$againstworktime['days'][$date]['time']."</time>\n";
				$local_xml_output .= "\t\t<type>".$againstworktime['days'][$date]['type']."</type>\n";
				$local_xml_output .= "\t\t<typelabel>".$againstworktime['days'][$date]['typelabel']."</typelabel>\n";
				$local_xml_output .= "\t</againstworktime>\n";
			} 	
		}
		$this->xmlOutput .= $local_xml_output;
	}
	
	/***
	** Use $timespan->getasworktime() to get array
	*/
	
	private function asworktimeXMLoutput($asworktime) {
		$local_xml_output = "";
		if(count($asworktime['days']) > 0){
			foreach (array_keys($asworktime['days']) as $date) {
				$local_xml_output .= "\t<asworktime>\n";
				$local_xml_output .= "\t\t<id>".$asworktime['days'][$date]['id']."</id>\n";
				$local_xml_output .= "\t\t<date>".$date."</date>\n";		
				$local_xml_output .= "\t\t<time>".$asworktime['days'][$date]['time']."</time>\n";
				$local_xml_output .= "\t\t<type>".$asworktime['days'][$date]['type']."</type>\n";
				$local_xml_output .= "\t\t<typelabel>".$asworktime['days'][$date]['typelabel']."</typelabel>\n";
				$local_xml_output .= "\t</asworktime>\n";
			} 	
		}
		$this->xmlOutput .=  $local_xml_output;
	}
	
	
	private function returnArrayXMLoutput($rarray, $mainkey) {
		$local_xml_output = "";
		$local_xml_output .= "\t<{$mainkey}>\n";
		foreach (array_keys($rarray) as $key) {
			if(is_array($rarray[$key][0])) {
				foreach ($rarray[$key] as $innerarray) {
					$local_xml_output .= "\t<{$key}>\n";
					foreach(array_keys($innerarray) as $innerkeys){
						$local_xml_output .= "\t\t<$innerkeys>".$innerarray[$innerkeys]."</$innerkeys>\n";
					}
					$local_xml_output .= "\t</{$key}>\n";
				}
			}
			else $local_xml_output .= "\t\t<$key>".$rarray[$key]."</$key>\n";
		} 
		$local_xml_output .= "\t</{$mainkey}>\n";
		return $local_xml_output;
	}
	
	
}

?>