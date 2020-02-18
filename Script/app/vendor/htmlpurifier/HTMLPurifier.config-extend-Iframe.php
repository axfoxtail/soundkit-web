<?php
// Iframe filter that does some primitive whitelisting in a
// somewhat recognizable and tweakable way
class HTMLPurifier_Filter_MyIframe extends HTMLPurifier_Filter {
    public $name = 'MyIframe';
    public function preFilter($html, $config, $context) {
        $html = preg_replace('/<iframe/i', '<img class="MyIframe"', $html);
        $html = preg_replace('#</iframe>#i', '', $html);
        return $html;
    }
    public function postFilter($html, $config, $context) {
        $post_regex = '#<img class="MyIframe"([^>]+?)>#';
        return preg_replace_callback($post_regex, array($this, 'postFilterCallback'), $html);
    }
    protected function postFilterCallback($matches) {
        // Whitelist the domains we like
        $ok = (preg_match('#src="http://www.youtube.com/#i', $matches[1]));
        if ($ok) {
            return '<iframe ' . $matches[1] . '></iframe>';
        } else {
            return '';
        }
    }
}