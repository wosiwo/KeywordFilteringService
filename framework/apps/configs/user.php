<?php
$user['default_avatar'] = '/static/images/default.png';
$user['login_table'] = 'user_login';
$user['login_url'] = '/user/login/';
/**
 *
 * CREATE TABLE IF NOT EXISTS `user_login` (
 * `id` int(11) NOT NULL AUTO_INCREMENT,
 * `username` varchar(64) NOT NULL,
 * `password` varchar(128) NOT NULL,
 * `realname` varchar(40) NOT NULL,
 * `lasttime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;
 */
return $user;