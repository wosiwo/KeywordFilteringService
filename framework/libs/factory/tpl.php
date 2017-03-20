<?php
$tpl = new Swoole\Template();
global $php;
$tpl->assign_by_ref('php', $php->env);

if (defined('TPL_DIR'))
{
    $tpl->template_dir = TPL_DIR;
}
elseif (is_dir(Swoole::$app_path . '/templates'))
{
    $tpl->template_dir = Swoole::$app_path . '/templates';
}
else
{
    $tpl->template_dir = WEBPATH . "/templates";
}
define('TPL_BASE', $tpl->template_dir);
if (DEBUG == 'on')
{
    $tpl->compile_check = true;
}
else
{
    $tpl->compile_check = false;
}
return $tpl;