ALTER TABLE /*$wgDBprefix*/bs_review ADD `rev_sequential` TINYINT( 3 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `rev_editable`;