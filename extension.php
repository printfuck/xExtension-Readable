<?php

class RedditImageExtension extends Minz_Extension {
    const DEFAULT_MUTEDVIDEO = true;
    const DEFAULT_DISPLAYIMAGE = true;
    const DEFAULT_DISPLAYVIDEO = true;

    public function init() {
        $this->registerTranslates();

        $current_user = Minz_Session::param('currentUser');

        $this->registerHook('entry_before_insert', array($this, 'fetchStuff'));
    }

    public function handleConfigureAction() {
        $this->registerTranslates();

        $current_user = Minz_Session::param('currentUser');
        $filename = 'configuration.' . $current_user . '.json';
        $filepath = join_path($this->getPath(), 'static', $filename);

        if (Minz_Request::isPost()) {
            $configuration = array(
                'imageHeight' => (int) Minz_Request::param('image-height', static::DEFAULT_HEIGHT),
                'mutedVideo' => (bool) Minz_Request::param('muted-video'),
                'displayImage' => (bool) Minz_Request::param('display-image'),
                'displayVideo' => (bool) Minz_Request::param('display-video'),
            );
            file_put_contents($filepath, json_encode($configuration));
            file_put_contents(join_path($this->getPath(), 'static', "style.{$current_user}.css"), sprintf(
                'img.reddit-image, video.reddit-image {max-height:%svh;}',
                $configuration['imageHeight']
            ));
        }

        $this->getConfiguration();
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
