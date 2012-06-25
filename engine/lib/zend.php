<?php
function elgg_zend_register_classes() {
	elgg_register_classes(dirname(dirname(__FILE__)) . '/classes/Zend', array(
		'prefix' => 'Zend',
		'glue' => '_',
		'recursively' => true,
	));
	set_include_path(dirname(dirname(__FILE__)).'/classes/'.PATH_SEPARATOR.dirname(dirname(__FILE__)).'/classes/Zend/'.PATH_SEPARATOR.get_include_path());
// 	Zend_Loader::loadClass('Zend_Db_Adapter_Mysqli');
}

elgg_register_event_handler('boot', 'system', 'elgg_zend_register_classes', 0);//before engine boot