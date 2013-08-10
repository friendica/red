<?php

// A toolbar for observers with write_pages permissions
function writepages_widget ($who,$which){
return replace_macros(get_markup_template('write_pages.tpl'), array(
			'$new' => t('New Page'),
			'$newurl' => "webpages/$who",
                        '$edit' => t('edit'),
                        '$editurl' => "editwebpage/$who/$which"
			));
}

