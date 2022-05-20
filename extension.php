<?php

class ReadabilityExtension extends Minz_Extension {

    public function init() {
        $this->registerTranslates();

        $current_user = Minz_Session::param('currentUser');

        $this->registerHook('entry_before_insert', array($this, 'fetchStuff'));
    }

    public function fetchStuff($entry) {
	
	$data = "{\"url\": \"" . $entry->link() ."\"}";
	$headers[] = 'Content-Type: application/json';

	$c = curl_init("http://read:3000/");
	curl_setopt($c, CURLOPT_POSTFIELDS, $data);
	curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($c);
	$c_status = curl_getinfo($c, CURLINFO_HTTP_CODE);
	//$c_error = curl_error($c);
	curl_close($c);

	if ($c_status !== 200) {
	    return $entry;
	}
	$val = json_decode($result, true);
	$entry->_content($val["content"]);
	return $entry;
    }

}
