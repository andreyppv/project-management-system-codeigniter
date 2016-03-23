<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// -- new_captcha -------------------------------------------------------------------------------------------------
/**
 * this function generates a captcha image. It uses CI's captcha helper to create the image
 * the 'word' used is then stored as a session variable 'captcha_word'
 * ttf font used (open sans) is stored in '/files/captcha' folder
 * captcha folder must be chmod to 777
 * 
 * @return	string  [captcha image url]
 */
if (!function_exists('template_function')) {
    function new_captcha()
    {

        //get $CI instance
        $ci = &get_instance();

        //load captch helper
        $ci->load->helper('captcha');

        //come vars
        $captcha_url = base_url() . 'files/captcha/';
        $captcha_word = random_string('alpha', 6);

        $vals = array(
            'word' => $captcha_word,
            'font_path' => PATHS_FONTS . 'OpenSans-Regular.ttf',
            'img_path' => PATHS_CAPTCHA_FOLDER,
            'img_url' => $captcha_url,
            'img_width' => '150',
            'img_height' => 45,
            'expiration' => 7200);

        $cap = create_captcha($vals);

        //save word used in sessions
        $ci->session->set_userdata('captacha_word', $captcha_word);

        //echo $cap['image'];

        //return full <img> tag
        return $cap['image'];

    }
}

// -- validate_captcha -------------------------------------------------------------------------------------------------
/**
 * validate a submitted captcha text against the one in session
 *
 * 
 * @return	string  [captcha image url]
 */
if (!function_exists('template_function')) {
    function validate_captcha($captcha_word = '')
    {

        //get $CI instance
        $ci = &get_instance();

        //verify match (made case insensitive by strtolower)
        if (strtolower($captcha_word) == strtolower($ci->session->userdata('captacha_word'))) {
            return true;
        } else {
            return false;
        }
    }
}

/* End of file mycaptcha_helper.php */
/* Location: ./application/helpers/mycaptcha_helper.php */
