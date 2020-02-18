<?php
Hook::getInstance()->register('admin.settings.integrations', function() {
    echo view('contact::admin/settings/integration');
});

Hook::getInstance()->register('home-footer-links', function() {
    echo '<a  href="'.url('contact').'">'.l('contact-us').'</a>';
});

Hook::getInstance()->register('home-footer-right', function() {
    echo '<a data-ajax="true" href="'.url('contact').'">'.l('contact-us').'</a>';
});
$request->any("contact", array('uses' => 'contact::contact@index', 'secure' => false));
