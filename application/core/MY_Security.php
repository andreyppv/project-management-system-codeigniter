<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * NEXTLOOP
 *
 * Main change was removing the filtering of 'style' tags ($evil_attributes) in HTML
 *          
 *
 */

class MY_Security extends CI_Security
{

    // --------------------------------------------------------------------

    /*
    * Remove Evil HTML Attributes (like evenhandlers and style)
    *
    * It removes the evil attribute and either:
    * 	- Everything up until a space
    *		For example, everything between the pipes:
    *		<a |style=document.write('hello');alert('world');| class=link>
    * 	- Everything inside the quotes
    *		For example, everything between the pipes:
    *		<a |style="document.write('hello'); alert('world');"| class="link">
    *
    * @param string $str The string to check
    * @param boolean $is_image TRUE if this is an image
    * @return string The string with the evil attributes removed
    */
    protected function _remove_evil_attributes($str, $is_image)
    {
        // All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
        $evil_attributes = array(
            'on\w*',
            'xmlns',
            'formaction');

        if ($is_image === true) {
            /*
            * Adobe Photoshop puts XML metadata into JFIF images, 
            * including namespacing, so we have to allow this for images.
            */
            unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
        }

        do {
            $count = 0;
            $attribs = array();

            // find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
            preg_match_all('/(' . implode('|', $evil_attributes) . ')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr) {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // find occurrences of illegal attribute strings without quotes
            preg_match_all('/(' . implode('|', $evil_attributes) . ')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr) {
                $attribs[] = preg_quote($attr[0], '/');
            }

            // replace illegal attribute strings that are inside an html tag
            if (count($attribs) > 0) {
                $str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)(' . implode('|', $attribs) . ')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
            }

        } while ($count);

        return $str;
    }

}

/* End of file MY_Security.php */
/* Location: ./application/core/MY_Security.php */
