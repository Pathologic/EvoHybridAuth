//<?php
/**
* HybridAuth
*
* Social sign on for Evolution CMS.
*
* @category plugin
* @version 	1.0.0
* @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
* @author   Vasiliy Naumkin (bezumkin@yandex.ru), Pathologic (m@xim.name)
* @internal	@properties &registerUsers=Register users;list;false,true;true &redirectUri=Callback url;text; &loginPage=Page to redirect to after login;text; &logoutPage=Page to redirect to after logout;text; &rememberme=Remember after login;list;false,true;true &cookieName=AutoLogin cookie name;text;WebLoginPE &cookieLifetime=AutoLogin cookie lifetime, seconds;text;157680000 &debug=Debug mode;list;false,true;false &userModel=User model;text; &tabName=Tab name;text;Hybrid Auth
* @internal	@events OnWebPageInit,OnPageNotFound,OnWebAuthentication,OnWebLogout,OnWUsrFormRender,OnWebDeleteUser
*/

require MODX_BASE_PATH.'assets/plugins/hybridauth/plugin.hybridauth.php';
