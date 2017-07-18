<?php
//
//  Copyright (c) 2009 Facebook
//
//  Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//  You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
//  Unless required by applicable law or agreed to in writing, software
//  distributed under the License is distributed on an "AS IS" BASIS,
//  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//  See the License for the specific language governing permissions and
//  limitations under the License.
//

//
// This file defines the interface iProfilerRuns and also provides a default
// implementation of the interface (class ProfilerRuns).
//

/**
 * iProfilerRuns interface for getting/saving a profiler run.
 *
 * Clients can either use the default implementation,
 * namely ProfilerRuns_Default, of this interface or define
 * their own implementation.
 *
 * @author Kannan
 */
interface iProfilerRuns {

  /**
   * Returns profiler data given a run id ($run) of a given
   * type ($type).
   *
   * Also, a brief description of the run is returned via the
   * $run_desc out parameter.
   */
  public function get_run($run_id, $type, &$run_desc);

  /**
   * Save profiler data for a profiler run of specified type
   * ($type).
   *
   * The caller may optionally pass in run_id (which they
   * promise to be unique). If a run_id is not passed in,
   * the implementation of this method must generated a
   * unique run id for this saved profiler run.
   *
   * Returns the run id for the saved profiler run.
   *
   */
  public function save_run($profiler_data, $type, $run_id = null);
}


/**
 * ProfilerRuns_Default is the default implementation of the
 * iProfilerRuns interface for saving/fetching profiler runs.
 *
 * It stores/retrieves runs to/from a filesystem directory
 * specified by the "profiler.output_dir" ini parameter.
 *
 * @author Kannan
 */
class ProfilerRuns_Default implements iProfilerRuns {

  private $dir = '';
  private $suffix = 'profiler';

  private function gen_run_id($type) {
    return uniqid();
  }

  private function file_name($run_id, $type) {

    $file = "$run_id.$type." . $this->suffix;

    if (!empty($this->dir)) {
      $file = $this->dir . "/" . $file;
    }
    return $file;
  }

    public function __construct($dir = null)
    {
      // if user hasn't passed a directory location,
      // we use the xxx.output_dir ini setting
      // if specified, else we default to the
      // traces directory (next to html and lib)
      $dirs = array(
        $dir,
        get_cfg_var("profiler.output_dir"),
        ini_get("uprofiler.output_dir"),
        ini_get("xhprof.output_dir"),
        '../traces',
        sys_get_temp_dir() . '/simple-profiler',
        '/tmp'
      );
      foreach ($dirs as $possibleDir) {
        if (!empty($possibleDir)
          && (is_dir($possibleDir) || @mkdir($possibleDir, 0777, true))
          && is_writable($possibleDir)
        ) {
          $this->dir = $possibleDir;
          return;
        }
      }
      die("Impossible to find a valid output dir.\n<br>\n" . __FILE__);
    }

  public function get_run($run_id, $type, &$run_desc) {
    $file_name = $this->file_name($run_id, $type);

    if (!file_exists($file_name)) {
      profiler_error("Could not find file $file_name");
      $run_desc = "Invalid Run Id = $run_id";
      return null;
    }

    $contents = file_get_contents($file_name);
    $run_desc = "profiler Run (Namespace=$type)";
    return unserialize($contents);
  }

  public function save_run($profiler_data, $type, $run_id = null) {

    // Use PHP serialize function to store the profiler's
    // raw profiler data.
    $profiler_data = serialize($profiler_data);

    if ($run_id === null) {
      $run_id = $this->gen_run_id($type);
    }

    $file_name = $this->file_name($run_id, $type);
    $file = fopen($file_name, 'w');

    if ($file) {
      fwrite($file, $profiler_data);
      fclose($file);
    } else {
      profiler_error("Could not open $file_name\n");
    }

    // echo "Saved run in {$file_name}.\nRun id = {$run_id}.\n";
    return $run_id;
  }

  function list_runs() {
    if (is_dir($this->dir)) {
        echo "<hr/>Existing runs:\n<ul>\n";
        $files = glob("{$this->dir}/*.{$this->suffix}");
        usort($files, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));
        foreach ($files as $file) {
            list($run,$source) = explode('.', basename($file));
            echo '<li><a href="' . htmlentities($_SERVER['SCRIPT_NAME'])
                . '?run=' . htmlentities($run) . '&source='
                . htmlentities($source) . '">'
                . htmlentities(basename($file)) . "</a><small> "
                . date("Y-m-d H:i:s", filemtime($file)) . "</small></li>\n";
        }
        echo "</ul>\n";
    }
  }
}
