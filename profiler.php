<?php

class JbnProfiler
{

    //Allowed ip as they can be read in $_SERVER['REMOTE_ADDR']
    //You might want to alter _getRemoteAddr() if you have a reverse proxy
    protected $_allowedIp = array(
        '10.0.0.0/8',           //Local network
        '172.16.0.0/12',        //Local network
        '192.168.0.0/16',       //Local network
    );

    //Can be set to false if method is not allowed
    protected $_enableKey = 'PROFILE';
    protected $_enableKeyGet = true;
    protected $_enableKeyPost = true;
    protected $_enableKeyCookie = true;
    protected $_enableKeyEnv = true;

    //If the key is truthy with this value then the trace will be triggered but output will not be altered
    //(footer will not be added, usefull for AJAX calls)
    protected $_silentWord = 'silent';

    //This class will use $_SERVER['HTTP_HOST'] as the namespace
    //Passing this parameter will allow to prefix it with something (useful for sorting profiles)
    //You can set this parameter to false to disallow some methods
    protected $_namespaceKey = 'PROFILENS';
    protected $_namespaceKeyGet = true;
    protected $_namespaceKeyPost = true;
    protected $_namespaceKeyCookie = true;
    protected $_namespaceKeyEnv = true;


    //Allows or disallow CLI usage of profiler
    protected $_allowedCli = true;

    //Base URL of the html folder of this projet
    protected $_baseUrl = 'http://localhost/html';

    //Directory for saving traces. Defaults to the first writable dir in:
    // $this->_traceDir, getenv('PROFILER_PATH'), ./traces, sys_get_temp_dir() . '/simple-profiler', '/tmp'
    protected $_tracesDir = null;

    //XHProf Flags for profiling, defaults to CPU + MEMORY
    protected $_flags = -1;

    //CSS passed in style attribute of the <div> and <a> added at the end of the page
    protected $_boxStyle = 'font-size:1.25em;border:solid 0.125em red;background:white;margin:1em;clear:both;text-align: center;';
    protected $_linkStyle = 'padding:0.25em 0.5em;margin:0.5em 0.25em;-webkit-appearance: button;-moz-appearance: button;appearance: button;text-decoration: none;color: initial;';

    /** @var ProfilerRuns_Default */
    private $_manager;

    public function __construct($params = array(), $debug = false)
    {
        foreach($params as $key => $value){
            if(!empty($value)){
                $varName = '_'.$key;
                $this->$varName = $value;
            }
        }
        if ($debug) {
            $this->debugProfilerEnabled();
        }
        if ($this->_profilerEnabled()) {
            //Include XHProf libs
            require_once __DIR__.'/lib/utils/lib.php';
            require_once __DIR__.'/lib/utils/runs.php';
            $this->_manager = new ProfilerRuns_Default($this->_tracesDir);


            //Register function that will stop profiling at the end
            register_shutdown_function(array($this, 'doShutdown'));

            //Begin profiling
            call_user_func($this->_getExtensionName().'_enable', $this->_getFlags());
        }
    }

    public function doShutdown()
    {
        //Stop profiling
        $profile = call_user_func($this->_getExtensionName().'_disable');
        $profileId = $this->_manager->save_run($profile, $this->_getProfileNamespace());
        $this->_displayFooter($profileId);
    }

    protected function _profilerEnabled()
    {
        return $this->_extensionLoaded() && $this->_enableKeyPresent() && $this->_allowed();
    }

    public function debugProfilerEnabled()
    {
        ini_set('display_errors', 1);

        echo '<br />Extension Loaded:' . ($this->_extensionLoaded() ? $this->_getExtensionName() : 'n');
        echo '<br />Get Found:' . ($this->_enableKeyGet() ? 'y' : 'n');
        echo '<br />Post Found:' . ($this->_enableKeyPost() ? 'y' : 'n');
        echo '<br />Cookie Found:' . ($this->_enableKeyCookie() ? 'y' : 'n');
        echo '<br />CLI:' . ($this->_isCli() ? 'y' : 'n');
        echo '<br />IP:' . $this->_getRemoteAddr();
        echo 'Allowed:' . ($this->_allowed() ? 'y' : 'n');
    }

    public function debugTracesFolder()
    {
        ini_set('display_errors', 1);

        $filename = $this->_tracesDir.'/touch';

        echo '<br />Traces dir:' . $this->_tracesDir;
        echo '<br />Writable:' . (is_writable($this->_tracesDir) ? 'y' : 'n');
        echo '<br />Write success:' . (touch($filename) ? 'y' : 'n');
        echo '<br />Delete success:' . (unlink($filename) ? 'y' : 'n');
    }

    protected function _extensionLoaded()
    {
        return (bool)$this->_getExtensionName();
    }

    protected function _enableKeyPresent()
    {
        return $this->_enableKeyGet() || $this->_enableKeyPost() || $this->_enableKeyCookie() || $this->_enableKeyEnv();
    }

    private function _enableKeyGet()
    {
        return ($this->_enableKeyGet && isset($_GET[$this->_enableKey])) ? $_GET[$this->_enableKey] : false;
    }

    private function _enableKeyPost()
    {
        return ($this->_enableKeyPost && isset($_POST[$this->_enableKey])) ? $_POST[$this->_enableKey] : false;
    }

    private function _enableKeyCookie()
    {
        return ($this->_enableKeyCookie && isset($_COOKIE[$this->_enableKey])) ? $_COOKIE[$this->_enableKey] : false;
    }

    private function _enableKeyEnv()
    {
        return ($this->_enableKeyEnv && isset($_SERVER[$this->_enableKey])) ? $_SERVER[$this->_enableKey] : false;
    }

    protected function _allowed()
    {
        //If CLI we only check allowedCli
        if ($this->_isCli()) {
            return $this->_allowedCli;
        }

        //Else, we check the IP
        $ip = ip2long($this->_getRemoteAddr());
        foreach ($this->_allowedIp as $range) {
            if (strstr($range, '/') !== false) {
                $range = explode('/', $range);
                $corr = (pow(2, 32) - 1) - (pow(2, 32 - $range[1]) - 1);
                $first = ip2long($range[0]) & ($corr);
                $length = pow(2, 32 - $range[1]) - 1;
                if ($ip >= $first || $ip <= ($first + $length)) {
                    return true;
                }
            } else {
                if ($ip == ip2long($range)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function _getProfileNamespace()
    {
        if ($get = $this->_getProfileNamespacePrefixGet()) {
            $namespace = $get . '_';
        } elseif ($post = $this->_getProfileNamespacePrefixPost()) {
            $namespace = $post . '_';
        } elseif ($cookie = $this->_getProfileNamespacePrefixCookie()) {
            $namespace = $cookie . '_';
        } elseif ($env = $this->_getProfileNamespacePrefixEnv()) {
            $namespace = $env . '_';
        } else {
            $namespace = '';
        }
        if($this->_isCli()){
            return $namespace . 'CLI';
        }
        return $namespace . str_replace('.','_',$_SERVER['HTTP_HOST']);
    }

    protected function _silentOutput()
    {
        return ($this->_enableKeyGet() == $this->_silentWord)
        || ($this->_enableKeyPost() == $this->_silentWord)
        || ($this->_enableKeyCookie() == $this->_silentWord)
        || ($this->_enableKeyEnv() == $this->_silentWord)
        || $this->_isNotHtml();
    }

    //Try to detect non HTML responses such as binary or XHR to not break them
    protected function _isNotHtml(){
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') //Browser XHR header
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'],'html') === false);                            //Browser does not expect HTML
    }

    private function _getProfileNamespacePrefixGet()
    {
        return ($this->_namespaceKeyGet && isset($_GET[$this->_namespaceKey])) ? $_GET[$this->_namespaceKey] : false;
    }

    private function _getProfileNamespacePrefixPost()
    {
        return ($this->_namespaceKeyPost && isset($_POST[$this->_namespaceKey])) ? $_POST[$this->_namespaceKey] : false;
    }

    private function _getProfileNamespacePrefixCookie()
    {
        return ($this->_namespaceKeyCookie && isset($_COOKIE[$this->_namespaceKey])) ? $_COOKIE[$this->_namespaceKey] : false;
    }

    private function _getProfileNamespacePrefixEnv()
    {
        return ($this->_namespaceKeyEnv && isset($_SERVER[$this->_namespaceKey])) ? $_SERVER[$this->_namespaceKey] : false;
    }

    protected function _getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * @return mixed
     */
    protected function _getRemoteAddr()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @return bool
     */
    protected function _isCli()
    {
        return PHP_SAPI == 'cli';
    }

    /**
     * @param $profileId
     */
    protected function _displayFooter($profileId)
    {
        if ($this->_silentOutput()) {
            return;
        }
        $urls = array(
            '📅 Profiler' => sprintf('%s/index.php?run=%s&source=%s', $this->_getBaseUrl(), $profileId, $this->_getProfileNamespace()),
            '🔀 Callgraph' => sprintf('%s/callgraph.php?run=%s&source=%s', $this->_getBaseUrl(), $profileId, $this->_getProfileNamespace()),
            '🕙 Previous runs' => $this->_getBaseUrl()
        );
        if ($this->_isCli()) {
            echo "\n------------------------------------------------------------------------------------------------------------\n";
            echo "- Profile path:\t\t{$this->_manager->get_dir()}/{$profileId}.{$this->_getProfileNamespace()}.{$this->_manager->get_suffix()}\n";
            foreach($urls as $title => $url){
                echo "- {$title}:\t{$url}\n";
            }
            echo "------------------------------------------------------------------------------------------------------------\n";
        } else {
            if($this->_enableKeyCookie()){
                $urls['❌ Disable persistence'] = 'javascript:(function(){document.cookie=\'' . $this->_enableKey .'=;expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/\'})()';
            } else {
                $urls['📌 Enable persistence'] = 'javascript:(function(){document.cookie=\'' . $this->_enableKey .'=1; path=/\'})()';
            }
            echo "<div style=\"{$this->_boxStyle}\">";
            foreach($urls as $title => $url){
                $pos = strpos($url, 'javascript:');
                if($pos === 0){
                    $js = substr($url,11);
                    echo "<a href=\"#\" onclick=\"{$js}\" style=\"{$this->_linkStyle}\">{$title}</a>";
                } else {
                    echo "<a href=\"{$url}\" target=\"_blank\" style=\"{$this->_linkStyle}\">{$title}</a>";
                }
            }
            echo "</div>";
        }
    }

    protected function _getExtensionName()
    {
        if (extension_loaded('tideways_xhprof')) {
            return 'tideways_xhprof';
        } else if (extension_loaded('tideways')) {
            return 'tideways';
        } else if (extension_loaded('uprofiler')) {
            return 'uprofiler';
        } else if(extension_loaded('xhprof')) {
            return 'xhprof';
        }
        return false;
    }

    /**
     * Get profiling flags compatible with xhprof or tideways
     * Value can be overloaded in construct(array('flags' => XHPROF_FLAGS_CPU))
     */
    protected function _getFlags(){
        if($this->_flags != -1){
            return $this->_flags;
        }
        $flagsCpu = constant(strtoupper($this->_getExtensionName()).'_FLAGS_CPU');
        $flagsMemory = constant(strtoupper($this->_getExtensionName()).'_FLAGS_MEMORY');
        return $flagsCpu + $flagsMemory;
    }
}
