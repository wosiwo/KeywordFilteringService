<?php
$default_fields = <<<HTML
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(128) NOT NULL,
  `url` varchar(64) NOT NULL,
  `extras` varchar(64) NOT NULL,
  `digest` tinyint(4) NOT NULL default '0',
  `keywords` varchar(50) NOT NULL,
  `showplace` varchar(16) NOT NULL default 'none',
  `color` varchar(7) NOT NULL default '#ff0000',
  `orderid` int(11) NOT NULL default '0',
  `catid` int(11) NOT NULL,
  `catname` varchar(32) NOT NULL,
  `readcount` int(11) NOT NULL,
  `addtime` timestamp NOT NULL default CURRENT_TIMESTAMP,
   KEY `orderid` (`orderid`),\n
HTML;
?>