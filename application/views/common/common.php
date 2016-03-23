<?php

// -- SESSION DATA -------------------------------------------------------------------------------------------------------
/**
 * - add all session data to debug
 * 
 */
$data['sessiondata'] = $this->session->all_userdata();

// -- DEBUGGING --------------------------------------------------------------------------------------------------------------
/**
 * - format debug data and send it to browser as echo or via modal
 * 
 */
//make the <pre></pre> output pretty
$pre_css = 'style="background-color: #FFF3FB; 
                          border: 1px solid #FD7CA3; 
                          border-radius: 4px 4px 4px 4px; 
                          color: #333333; display: block; 
                          font-size: 13px; 
                          line-height: 1.42857;
                          margin:0 auto 10px;padding: 9.5px;
                          word-break: break-all;
                          word-wrap: break-word;
                          width:90%"';

//<pre> format main data array
ob_start();
echo "<pre $pre_css>";
echo '<STRONG><h2>Debugging & Profiling</h2></STRONG><br/>';
$data['conf'] = $conf;
foreach ($data as $key => $value) {
    if (is_array($data[$key])) {
        echo "<pre><pre><strong>[$key]</strong></pre>";
        pretty_print_r($value);
        echo "</pre>";
    } else {
        echo "<pre>$value</pre>";
    }
}
echo '</pre>';
$all_data = ob_get_contents();
ob_end_clean();

// if debug level 2: send to modal
if ($this->config->item('debug_mode') == 2) {
    $data['vars']['debug_mode'] = 2;
    $data['vars']['debuuging_output'] = $all_data;
}

// -- TBS ----------------------------------------------------------------------------------------------------------------
/**
 * - send all the data to TBS for merging and rendering to the browser
 * 
 */
//ensure index are set
$data['template_file'] = (isset($data['template_file'])) ? $data['template_file'] : array();
$data['template'] = (isset($data['template'])) ? $data['template'] : array();
$data['row'] = (isset($data['row'])) ? $data['row'] : array();
$data['notices'] = (isset($data['notices'])) ? $data['notices'] : array();
$data['config'] = (isset($data['config'])) ? $data['config'] : array();
$data['visible'] = (isset($data['visible'])) ? $data['visible'] : array();
$data['access'] = (isset($data['access'])) ? $data['access'] : array();
$data['js_validation'] = (isset($data['js_validation'])) ? $data['js_validation'] : array();
$data['js_validation_message'] = (isset($data['js_validation_message'])) ? $data['js_validation_message'] : array();
$data['lists'] = (isset($data['lists'])) ? $data['lists'] : array();
$data['sessiondata'] = (isset($data['sessiondata'])) ? $data['sessiondata'] : array();
$data['project_permissions'] = (isset($data['project_permissions'])) ? $data['project_permissions'] : array();
$data['permission'] = (isset($data['permission'])) ? $data['permission'] : array();
$data['lang'] = (isset($data['lang'])) ? $data['lang'] : array();
$data['version'] = (isset($data['version'])) ? $data['version'] : array();
$data['count'] = (isset($data['count'])) ? $data['count'] : array();
$data['vars'] = (isset($data['vars'])) ? $data['vars'] : array();
$all_data = (isset($all_data))? $all_data : array();
$post = (isset($_POST))? $_POST : array();
$conf = (isset($conf))? $conf : array();

/*function f_utf8_conv($x) {
  return htmlspecialchars(utf8_encode($x));
}

echo f_utf8_conv($data['lang']['lang_pending']);
*/


//load TBS
$this->load->library('tbs');
$this->tbs->NoErr = true;
$this->tbs->LoadTemplate($data['template_file'], 'FALSE');
$this->tbs->MergeField('template', $data['template']);
$this->tbs->MergeField('row', $data['row']);
$this->tbs->MergeField('debugging', $all_data);
$this->tbs->MergeField('notices', $data['notices']);
$this->tbs->MergeField('conf', $conf);
$this->tbs->MergeField('post', $post);
$this->tbs->MergeField('config', $data['config']);
$this->tbs->MergeField('visible', $data['visible']);
$this->tbs->MergeField('access', $data['access']);
$this->tbs->MergeField('js_validation', $data['js_validation']);
$this->tbs->MergeField('js_validation_message', $data['js_validation_message']);
$this->tbs->MergeField('lists', $data['lists']);
$this->tbs->MergeField('sessiondata', $data['sessiondata']);
$this->tbs->MergeField('project_permissions', $data['project_permissions']);
$this->tbs->MergeField('permission', $data['permission']);
$this->tbs->MergeField('lang', $data['lang']);
$this->tbs->MergeField('version', $data['version']);

//merge all registered data blocks - removing duplicates
$this->data['reg_blocks'] = (isset($this->data['reg_blocks'])) ? $this->data['reg_blocks'] : array();
$reg_blocks = array_unique($this->data['reg_blocks']);
foreach ($reg_blocks as $value) {
    $this->tbs->MergeBlock($value, $data['blocks'][$value]);
}

//merge all registered data fields - removing duplicates
$this->data['reg_fields'] = (isset($this->data['reg_fields'])) ? $this->data['reg_fields'] : array();
$reg_fields = array_unique($this->data['reg_fields']);
foreach ($reg_fields as $value) {
    $this->tbs->MergeField($value, $data['fields'][$value]);
}

//merge allrows (1-10)
for ($i = 0; $i < 10; $i++) {
    $rows = "rows$i";
    if (isset($data[$rows]) && is_array($data[$rows])) {
        $this->tbs->MergeField($rows, $data[$rows]);
    }
}

//merge blocks (blk1-blk10)
for ($i = 0; $i < 10; $i++) {
    $block = "blk$i";
    if (isset($data[$block]) && is_array($data[$block])) {
        $this->tbs->MergeBlock($block, $data[$block]);
    }
}

//merge vars & counts right at the end. to allow block merging using them conditionally
//e.g. class="[vars.css_menu_tasks_side_[tasks_milestones.milestones_id;block=li]]
$this->tbs->MergeField('count', $data['count']);
$this->tbs->MergeField('vars', $data['vars']);

//show the page
$this->tbs->Show(TBS_OUTPUT);

unset($this->tbs);

// -- ECHO DEBUG-----------------------------------------------------------------------------------------------------------
/**
 * - if system is running in debug_mode level:1, echo debug data at footer of page
 * 
 */
if ($this->config->item('debug_mode') == 1) {
    echo str_replace('&gt;', '>', $all_data);
}

/* End of file common.php */
/* Location: ./application/views/common/common.php */
