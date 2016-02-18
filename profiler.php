<?php

class JbnProfiler
{
    const SILENT_WORD = 'silent';
    const ENABLE_KEY = 'XHPROF';
    const NS_KEY = 'XHPROFNS';

    //Allowed ip as they can be read in $_SERVER['REMOTE_ADDR']
    //You might want to alter _getRemoteAddr() if you have a reverse proxy
    protected $_allowedIp = array(
        '10.0.0.0/8',           //Local network
        '172.16.0.0/12',        //Local network
        '192.168.0.0/16',       //Local network
    );

    //Can be set to false if method is not allowed
    protected $_enableKeyGet = true;
    protected $_enableKeyPost = true;
    protected $_enableKeyCookie = true;
    protected $_enableKeyEnv = true;

    //This class will use $_SERVER['HTTP_HOST'] as the namespace
    //Passing this parameter will allow to prefix it with something (useful for sorting profiles)
    //You can set this parameter to false to disallow some methods
    protected $_namespaceKeyGet = true;
    protected $_namespaceKeyPost = true;
    protected $_namespaceKeyCookie = true;
    protected $_namespaceKeyEnv = true;


    //Allows or disallow CLI usage of profiler
    protected $_allowedCli = true;

    //Base URL of XHPROF html (usually found in /usr/share/php/xhprof_html after module installation)
    protected $_baseUrl = 'http://localhost/xhprof';

    //Base URL of XHPROF lib path
    protected $_baseLibPath = '/usr/share/php/xhprof_lib/';


    public function __construct()
    {
        if ($this->_profilerEnabled()) {
            //Tries to create dir if it as been deleted
            if(!is_dir($this->_getOutputDir())) {
                mkdir($this->_getOutputDir(), 0777, true);
            }

            //Include XHProf libs
            require_once $this->_baseLibPath . 'utils/xhprof_lib.php';
            require_once $this->_baseLibPath . 'utils/xhprof_runs.php';

            //Register function that will stop profiling at the end
            register_shutdown_function(array($this, 'doShutdown'));

            //Begin profiling
            xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
        }
    }

    public function doShutdown()
    {
        //Stop profiling
        $profile = xhprof_disable();
        $manager = new XHProfRuns_Default();
        $profileId = $manager->save_run($profile, $this->_getProfileNamespace());
        $this->_displayFooter($profileId);
    }

    protected function _profilerEnabled()
    {
        return $this->_extensionLoaded() && $this->_enableKeyPresent() && $this->_allowed();
    }

    public function debugProfilerEnabled()
    {
        ini_set('display_errors', 1);

        echo '<br />Extension Loaded:' . ($this->_extensionLoaded() ? 'y' : 'n');
        echo '<br />Get Found:' . ($this->_enableKeyGet() ? 'y' : 'n');
        echo '<br />Post Found:' . ($this->_enableKeyPost() ? 'y' : 'n');
        echo '<br />Cookie Found:' . ($this->_enableKeyCookie() ? 'y' : 'n');
        echo '<br />CLI:' . ($this->_isCli() ? 'y' : 'n');
        echo '<br />IP:' . $this->_getRemoteAddr();
        echo 'Allowed:' . ($this->_allowed() ? 'y' : 'n');
    }

    protected function _extensionLoaded()
    {
        return extension_loaded('xhprof');
    }

    protected function _enableKeyPresent()
    {
        return $this->_enableKeyGet() || $this->_enableKeyPost() || $this->_enableKeyCookie() || $this->_enableKeyEnv();
    }

    private function _enableKeyGet()
    {
        return ($this->_enableKeyGet && isset($_GET[self::ENABLE_KEY])) ? $_GET[self::ENABLE_KEY] : false;
    }

    private function _enableKeyPost()
    {
        return ($this->_enableKeyPost && isset($_POST[self::ENABLE_KEY])) ? $_POST[self::ENABLE_KEY] : false;
    }

    private function _enableKeyCookie()
    {
        return ($this->_enableKeyCookie && isset($_COOKIE[self::ENABLE_KEY])) ? $_COOKIE[self::ENABLE_KEY] : false;
    }

    private function _enableKeyEnv()
    {
        return ($this->_enableKeyEnv && isset($_SERVER[self::ENABLE_KEY])) ? $_SERVER[self::ENABLE_KEY] : false;
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
        return $namespace . $_SERVER['HTTP_HOST'];
    }

    protected function _silentOutput()
    {
        return ($this->_enableKeyGet() == self::SILENT_WORD)
        || ($this->_enableKeyPost() == self::SILENT_WORD)
        || ($this->_enableKeyCookie() == self::SILENT_WORD)
        || ($this->_enableKeyEnv() == self::SILENT_WORD);
    }

    private function _getProfileNamespacePrefixGet()
    {
        return ($this->_namespaceKeyGet && isset($_GET[self::NS_KEY])) ? $_GET[self::NS_KEY] : false;
    }

    private function _getProfileNamespacePrefixPost()
    {
        return ($this->_namespaceKeyPost && isset($_POST[self::NS_KEY])) ? $_POST[self::NS_KEY] : false;
    }

    private function _getProfileNamespacePrefixCookie()
    {
        return ($this->_namespaceKeyCookie && isset($_COOKIE[self::NS_KEY])) ? $_COOKIE[self::NS_KEY] : false;
    }

    private function _getProfileNamespacePrefixEnv()
    {
        return ($this->_namespaceKeyEnv && isset($_SERVER[self::NS_KEY])) ? $_SERVER[self::NS_KEY] : false;
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
            'Profiler output' => sprintf('%s/index.php?run=%s&source=%s', $this->_getBaseUrl(), $profileId, $this->_getProfileNamespace()),
            'Full callgraph' => sprintf('%s/callgraph.php?run=%s&source=%s', $this->_getBaseUrl(), $profileId, $this->_getProfileNamespace()),
            'Existing runs' => $this->_getBaseUrl()
        );
        if ($this->_isCli()) {
            echo "\n------------------------------------------------------------------------------------------------------------\n";
            echo "- Profile path:\t\t{$this->_getOutputDir()}/{$profileId}.{$this->_getProfileNamespace()}.xhprof\n";
            foreach($urls as $title => $url){
                echo "- {$title}:\t{$url}\n";
            }
            echo "------------------------------------------------------------------------------------------------------------\n";
        } else {
            echo "<div style=\"font-size:20px;border:solid 5px red;background:white;margin:10px;padding:10px;clear:both;text-align: center;\">";
            foreach($urls as $title => $url){
                echo "<a href=\"{$url}\" target=\"_blank\">{$title}</a><br>";
            }
            echo "</div>";
        }
    }

    /**
     * @return string
     */
    protected function _getOutputDir()
    {
        return ini_get('xhprof.output_dir');
    }
}