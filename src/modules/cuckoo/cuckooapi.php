<?php
	
class CuckooAPI 
{
	const URL_WEB_BROWSE 		= ''; //'browse/page/1';
	const URL_CREATE_TASK_FILE 	= 'tasks/create/file';
	const URL_CREATE_TASK_URL 	= 'tasks/create/url';
	const URL_CREATE_TASK_SUBMIT = 'tasks/create/submit';
	const URL_TASKS_LIST 		= 'tasks/list';
	const URL_TASK_VIEW 		= 'tasks/view';
	const URL_TASK_RESCHEDULE	= 'tasks/reschedule';
	const URL_TASK_DELETE 		= 'tasks/delete';
	const URL_TASK_REPORT		= 'tasks/report';
	const URL_TASK_SCREEN		= 'tasks/screenshots';
	const URL_TASK_REREPORT		= 'tasks/rereport';
	const URL_TASK_REBOOT		= 'tasks/reboot';
	const URL_MEMORY_LIST		= 'memory/list';
	const URL_MEMORY_GET		= 'memory/get';
	const URL_FILE_VIEW 		= 'files/view';
	const URL_FILE_CONTENT 		= 'files/get';
	const URL_PCAP_GET 			= 'pcap/get';
	const URL_MACHINES_LIST 	= 'machines/list';
	const URL_MACHINES_VIEW 	= 'machines/view';
	const URL_CUCKOO_STATUS		= 'cuckoo/status';
	const URL_VPN_STATUS		= 'vpn/status';
	const URL_VIEW_URL			= 'analysis';
	
	const ERROR_FILE_TOO_BIG	= -5;
	const ERROR_FILE_NOT_FOUND	= -4;
	const ERROR_API_ERROR 		= -3;
	const ERROR_FILE_UNKNOWN	= -2;
	const ERROR_FILE_BEING_ANALYZED = -1;	
	const ERROR_FILE_FOUND		= 0;

	private $web_base_url;
	private $scan_params;
	
	public function __construct($web_base_url, $api_base_url, $scan_params) {		
		$this->web_base_url = $web_base_url;
		$this->api_base_url = $api_base_url;
		$this->scan_params = $scan_params;
	}
	
	public function getVersion() {
		$ret = $this->_doCall('GET', CuckooAPI::URL_CUCKOO_STATUS, array());
		if (isset($ret) && isset($ret->version)) {
			return $ret->version;
		}
		return '';
	}
	
	public function getInfos() {
		return $this->_doCall('GET', CuckooAPI::URL_CUCKOO_STATUS, array());
	}
	
	public function getMachines() {
		return $this->_doCall('GET', CuckooAPI::URL_MACHINES_LIST, array());
	}
	
	public function getReportUrl($id) {
		return $this->web_base_url . CuckooAPI::URL_VIEW_URL . '/' . $id;
	}
	
	public function getBrowseUrl() {
		return $this->web_base_url . CuckooAPI::URL_WEB_BROWSE;
	}
	
	public static function getIdFromUrl($url) {
		if (strpos($url, 'http') === false)
			return $url;
		else
			return substr(strrchr($url, "/"), 1);
	}
	
	/**
	 * Send and scan a file.
	 *
	 * @param string $filePath A relative path to the file.
	 * @return object An object containing the scan ID for getting the report later:
	 */
	public function scanFile($filePath,$fileName="",$options=array()) {		
		if (!file_exists($filePath)) {
			return array(
					'response_code' => CuckooAPI::ERROR_FILE_NOT_FOUND
				);
		}
		
		$realPath = realpath($filePath);

		if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
			$pathInfo = pathinfo($realPath);
			if (empty($fileName)) $fileName = $pathInfo['basename'];
		
			$file_to_upload = new CURLFile($realPath, mime_content_type($filePath), $fileName);			
			$parameters = array(
					'file' => $file_to_upload
				);
		} else if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
			$pathInfo = pathinfo($realPath);
			if (empty($fileName)) $fileName = $pathInfo['basename'];
		
			$parameters = array(
					'file' => '@' . $realPath . ';' .
					'type=' . mime_content_type($filePath) . ';' .
					'filename=' . $fileName
				);
		} else {
			// Due to a bug in some older curl versions
			// we only send the file without mime type or file name.
			$parameters = array(
					'file' => '@' . $realPath
				);
		}
        
		$parameters = array_merge($parameters, $this->scan_params);
		$parameters = array_merge($parameters, $options);
		return $this->_doPost(CuckooAPI::URL_CREATE_TASK_FILE, $parameters);
	}
	
	public function scanUrl($url) {
	
		$parameters = array(
				'url' => $url
			);
		
		return $this->_doPost(CuckooAPI::URL_CREATE_TASK_URL, $parameters);
	}
	
	public function getTasks($count = -1, $offset = -1) {		
		$url = CuckooAPI::URL_TASKS_LIST;
		if ($count != -1) 	$url = $url . "/" . strval($count);
		if ($offset != -1) 	$url = $url . "/" . strval($offset);
		return $this->_doGet($url);
	}
	
	public function getTask($id) {		
		$ret = $this->_doGet(CuckooAPI::URL_TASK_VIEW . '/' . $id);
		$version = $this->getVersion();
		if (is_array($ret) && isset($ret['response_code']) && $ret['response_code'] == '-3' && !empty($version)){
			return array(
				'response_code' => CuckooAPI::ERROR_FILE_NOT_FOUND
			);		
		}
		return $ret;
	}
	
	public function deleteTask($id) {		
		return $this->_doGet(CuckooAPI::URL_TASK_DELETE . '/' . $id);
	}
	
	public function getReport($id) {
		return $this->_doGet(CuckooAPI::URL_TASK_REPORT . '/' . $id . '/json');
	}
	
	public function getScrenshots($id) {		
		return $this->_doGet(CuckooAPI::URL_TASK_SCREEN . '/' . $id);
	}
	
	public function getFileReport($md5hash) {		
		return $this->_doGet(CuckooAPI::URL_FILE_VIEW . '/md5/' . $md5hash);
	}
	
	public function getFile($sha256hash) {		
		return $this->_doGet(CuckooAPI::URL_FILE_CONTENT . '/' . $sha256hash);
	}
	
	private function _doPost($apiTarget, $parameters) {			
		return $this->_doCall('POST', $apiTarget, $parameters);
	}
	
	private function _doGet($apiTarget) {
		return $this->_doCall('GET', $apiTarget, array());
	}
	
	private function _doCall($method, $apiTarget, $parameters) {
		$postFields = array();
		$postFields = array_merge($parameters, $postFields);

		$ch = curl_init($this->api_base_url . $apiTarget);
		@curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		@curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($ch, CURLOPT_VERBOSE, 0);
		@curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
		@curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); 
		@curl_setopt($ch, CURLOPT_TIMEOUT, 400);
		
		if ($method == 'POST') {
			@curl_setopt($ch, CURLOPT_POST, true);
			@curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		}
		
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
		curl_close($ch);

		if ($httpCode >= '400' && $httpCode < '500' ) {
			return array(
					'response_code' => CuckooAPI::ERROR_API_ERROR
				);
		} elseif ($httpCode >= '500' && $httpCode < '600' ) {
			return array(
					'response_code' => CuckooAPI::ERROR_API_ERROR
				);
		} else
			return json_decode($response);
	}
}
?>