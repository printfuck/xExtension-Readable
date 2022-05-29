<?php

class ReadabilityExtension extends Minz_Extension {

    private $readHost;
    private $mercHost;
    private $feeds;
    private $mStore;
    private $rStore;

    public function init() {
        #$this->registerTranslates();

	    #$current_user = Minz_Session::param('currentUser');
	Minz_View::appendScript($this->getFileUrl('read_ext.js', 'js'));

        $this->registerHook('entry_before_insert', array($this, 'fetchStuff'));
    }

    public function fetchStuff($entry) {
	
	$this->loadConfigValues();
	/*
	$read = false;
			
    	$regex = [
		'nytimes.com',
		'ncsjdnfsd.de'
	];

	foreach ( $regex as $ex ) {
		if (false !== strpos($entry->link(), $ex ) ) {
			$read = true;
		}
	}
	 */
	$host = '';

	if ( array_key_exists($entry->feed(false), $this->mStore) )
		$host = $this->mercHost;

	if ( array_key_exists($entry->feed(false), $this->rStore) )
		$host = $this->readHost;

	if ($host === '')
		return $entry;

	/*if (! $read){
		return $entry;
	}*/
	$data = "{\"url\": \"" . $entry->link() ."\"}";
	$headers[] = 'Content-Type: application/json';

	$c = curl_init($host);
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

    public function getReadHost() {
	    return $this->readHost;
    }

    public function getMercHost() {
	    return $this->mercHost;
    }
    
    public function getFeeds() {
	    return $this->feeds;
    }

    public function loadConfigValues()
    {
        if (!class_exists('FreshRSS_Context', false) || null === FreshRSS_Context::$user_conf) {
            return;
	}

        if (FreshRSS_Context::$user_conf->read_ext_read_host != '') {
            $this->readHost = FreshRSS_Context::$user_conf->read_ext_read_host;
        }
        if (FreshRSS_Context::$user_conf->read_ext_merc_host != '') {
            $this->mercHost = FreshRSS_Context::$user_conf->read_ext_merc_host;
        }
        if (FreshRSS_Context::$user_conf->read_ext_mercury != '') {
            $this->mStore = json_decode(FreshRSS_Context::$user_conf->read_ext_mercury, true);
	} else {
	    $this->mStore = [];
	}
        if (FreshRSS_Context::$user_conf->read_ext_readability != '') {
            $this->rStore = json_decode(FreshRSS_Context::$user_conf->read_ext_readability, true);
	} else {
	    $this->rStore = [];
	}
    }

    public function getConfStore( $list, $id ) {
    	if ($list === 'm' ) {
		return array_key_exists($id, $this->mStore);
	} else {
		return array_key_exists($id, $this->rStore);
	}
	return false;
    }

    public function handleConfigureAction()
    {
	//$this->registerTranslates();

	$feedDAO = FreshRSS_Factory::createFeedDao();
	$this->feeds = $feedDAO->listFeeds();

	if (Minz_Request::isPost()) {
	    //FreshRSS_Context::$user_conf->yt_nocookie = (int)Minz_Request::param('yt_nocookie', 0);
	    $mstore = [];
	    $rstore = [];
	    foreach ( $this->feeds as $f ) {	
		    if ((bool)Minz_Request::param("read_".$f->id(), 0)){
			    $rstore[$f->id()] = true;
		    }

		    if ( (bool)Minz_Request::param("merc_".$f->id(), 0) ) {
			    $mstore[$f->id()] = true;
		    }
	    }
	
	    FreshRSS_Context::$user_conf->read_ext_mercury = (string)json_encode($mstore);
	    FreshRSS_Context::$user_conf->read_ext_readability = (string)json_encode($rstore);


	    FreshRSS_Context::$user_conf->read_ext_merc_host = (string)Minz_Request::param('read_mercury_host');
	    FreshRSS_Context::$user_conf->read_ext_read_host = (string)Minz_Request::param('read_readability_host');
	
	    FreshRSS_Context::$user_conf->save();
	}


	$this->loadConfigValues();
    }



}
