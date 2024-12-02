<?php

class ReadableExtension extends Minz_Extension {

    private $readHost;
    private $mercHost;
    private $fiveHost;
    private $feeds;
    private $cats;
    private $mStore;
    private $rStore;
    private $fStore;
    private $cStore;

    public function init() {

	    $this->registerHook('entry_before_insert', array($this, 'fetchStuff'));
  	    Minz_View::appendStyle($this->getFileUrl('style.css', 'css'));
    }

    public function fetchStuff($entry) {
	
	$this->loadConfigValues();
	$host = '';
	if (empty($entry->toArray()['id_feed'])){
		$id = $entry->feed(false);
	} else { 
		$id = $entry->toArray()['id_feed'];
	}

	$catid = $entry->feed()->category()->id();
    
	if ( array_key_exists($id, $this->mStore) || array_key_exists($catid, $this->cStore["merc"]) ) {
		$host = $this->mercHost."/parser?url=".$entry->link();
		$c = curl_init($host);
    }

	if ( array_key_exists($id, $this->rStore) || array_key_exists($catid, $this->cStore["read"]) ) {
		$host = $this->readHost;
		$c = curl_init($host);
		$data = "{\"url\": \"" . $entry->link() ."\"}";
		$headers[] = 'Content-Type: application/json';
		curl_setopt($c, CURLOPT_POSTFIELDS, $data);
		curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
	}
	
	if ( array_key_exists($id, $this->fStore) || array_key_exists($catid, $this->cStore["ff"]) ) {
		$host = $this->fiveHost."/extract.php?url=".$entry->link();
		$c = curl_init($host);
    }

	if ($host === '')
		return $entry;

	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($c);
	$c_status = curl_getinfo($c, CURLINFO_HTTP_CODE);
	//$c_error = curl_error($c);
	curl_close($c);

	if ($c_status !== 200) {
		return $entry;
	}
	$val = json_decode($result, true);
	if (empty($val) || empty($val["content"])) {
		return $entry;
	}
	$entry->_content($val["content"]);
	return $entry;
    }

    /*
     * These are called from configure.phtml, which is controlled by handleConfigureAction(), 
     * thus values are already fetched from userconfig and FeedDAO.
     */

    public function getReadHost() {
	    return $this->readHost;
    }

    public function getMercHost() {
	    return $this->mercHost;
    }

    public function getFiveHost() {
	    return $this->fiveHost;
    }

    public function getFeeds() {
	    return $this->feeds;
    }

    public function getCategories() {
	    return $this->cats;
    }

    /*
    Loading basic variables from user storage
    */
    public function loadConfigValues(){
	if (!class_exists('FreshRSS_Context', false) || null === FreshRSS_Context::$user_conf) {
		echo "Failed data";
    		return;
	}

	if (FreshRSS_Context::$user_conf->read_ext_read_host != '') {
		$this->readHost = FreshRSS_Context::$user_conf->read_ext_read_host;
	}
	if (FreshRSS_Context::$user_conf->read_ext_merc_host != '') {
		$this->mercHost = FreshRSS_Context::$user_conf->read_ext_merc_host;
	}
	if (FreshRSS_Context::$user_conf->read_ext_five_host != '') {
		$this->fiveHost = FreshRSS_Context::$user_conf->read_ext_five_host;
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
        if (FreshRSS_Context::$user_conf->read_ext_five != '') {
		$this->fStore = json_decode(FreshRSS_Context::$user_conf->read_ext_five, true);
	} else {
		$this->fStore = [];
	}
        if (FreshRSS_Context::$user_conf->read_ext_cat != '') {
		$this->cStore = json_decode(FreshRSS_Context::$user_conf->read_ext_cat, true);
	} else {
		$this->cStore = ["ff" => [], "merc" => [], "read" => []];
	}
    }

    public function getConfStoreR($id ) {
		return array_key_exists($id, $this->rStore);
    }
    public function getConfStoreM($id ) {
		return array_key_exists($id, $this->mStore);
    }
    public function getConfStoreF($id ) {
		return array_key_exists($id, $this->fStore);
    }
    public function getConfStoreCat($str, $id ) {
	    return $id != 'all' ? array_key_exists($id, $this->cStore[$str]) : 
		    array_key_exists($id, $this->cStore["ff"]) || 
		    array_key_exists($id, $this->cStore["read"]) || 
		    array_key_exists($id, $this->cStore["merc"]); 
    }
    
    /*
     * handleConfigureAction() is only executed on loading and saving the extenstion's configuration page.
     * If the Request type is POST, values are being saved. It looks weird, but I copied it from another example and it works flawlessly.
     */
    public function handleConfigureAction()
    {
	$feedDAO = FreshRSS_Factory::createFeedDao();
	$catDAO = FreshRSS_Factory::createCategoryDao();
	$this->feeds = $feedDAO->listFeeds();
	$this->cats = $catDAO->listCategories(true,false); 

	if (Minz_Request::isPost()) {
	    $mstore = [];
	    $rstore = [];
	    $fstore = [];
	    $cstore = ["ff" => [], "merc" => [], "read" => []];
	    foreach ( $this->feeds as $f ) {
	            //I rather encode only a few 'true' entries, than 400+ false entries + the few 'true' entries	    
		    if ((bool)Minz_Request::param("read_".$f->id(), 0)){
			    $rstore[$f->id()] = true;
		    }

		    if ((bool)Minz_Request::param("merc_".$f->id(), 0) ) {
			    $mstore[$f->id()] = true;
		    }

		    if ((bool)Minz_Request::param("ff_".$f->id(), 0) ) {
			    $fstore[$f->id()] = true;
		    }
	    }

	    foreach ( $this->cats as $c ) {
	    	foreach ( array_keys($cstore) as $v ) {
		    if ((bool)Minz_Request::param($v . "_cat_".$c->id(), 0)){
			    $cstore[$v][$c->id()] = true;
		
		    }
		}
	    }
	    // Json encoded, so you can easily view and debug in the user config file
	    FreshRSS_Context::$user_conf->read_ext_mercury = (string)json_encode($mstore);
	    FreshRSS_Context::$user_conf->read_ext_readability = (string)json_encode($rstore);
	    FreshRSS_Context::$user_conf->read_ext_five = (string)json_encode($fstore);
	    FreshRSS_Context::$user_conf->read_ext_cat = (string)json_encode($cstore);


	    FreshRSS_Context::$user_conf->read_ext_merc_host = (string)Minz_Request::param('read_mercury_host');
	    FreshRSS_Context::$user_conf->read_ext_read_host = (string)Minz_Request::param('read_readability_host');
	    FreshRSS_Context::$user_conf->read_ext_five_host = (string)Minz_Request::param('read_fivefilters_host');
	
	    FreshRSS_Context::$user_conf->save();
	}

	$this->loadConfigValues();
    }
}
