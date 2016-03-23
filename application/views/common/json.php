<?php

// -- json outputting for ajax controllers--------------------------------------------------------------------------------------------------
/**
 * - $data is normally a json_encoded array
 *
 */

if (!is_array($data)) {
    $data = array();
}
echo json_encode($data);


/* End of file json.php */
/* Location: ./application/views/common/json.php */
