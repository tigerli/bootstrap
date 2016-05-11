<?php

namespace Auth\Controllers;

use Phwoolcon\Auth\Adapter\Exception as AuthException;
use Phwoolcon\Auth\Auth;
use Phwoolcon\Controller;
use Phwoolcon\Log;
use Phwoolcon\Router;

class AccountController extends Controller
{

    protected function checkLoggedInUser()
    {
        return Auth::getInstance()->getUser();
    }

    public function getForgotPassword()
    {
        $this->addPageTitle(__('Forgot password'));
        $this->render('account', 'forgot-password');
    }

    public function getIndex()
    {
        if (!$user = $this->checkLoggedInUser()) {
            $this->flashSession->error(__('Please login first'));
            $this->redirect('account/login');
            return;
        }
        $this->render('account', 'index');
    }

    public function getLogin()
    {
        $this->rememberRedirectUrl();
        $this->addPageTitle(__('Login'));
        $this->render('account', 'login');
    }

    public function getLogout()
    {
        $this->rememberRedirectUrl();
        Auth::getInstance()->logout();
        $this->flashSession->success(__('Logout success'));
        return $this->redirect(url('account/redirect'));
    }

    public function getRedirect()
    {
        $this->rememberRedirectUrl();
        $this->addPageTitle(__('Redirecting'));
        $this->render('account', 'redirect', ['config' => [
            'url' => $this->session->get('redirect_url', url('account'), true),
            'timeout' => Auth::getOption('redirect_timeout') * 1000,
        ]]);
    }

    public function getRegister()
    {
        $this->rememberRedirectUrl();
        $this->addPageTitle(__('Register'));
        $this->render('account', 'register');
    }

    public function postForgotPassword()
    {}

    public function postLogin()
    {
        $this->rememberRedirectUrl();
        $credential = $this->request->getPost('auth');
        try {
            Auth::getInstance()->login($credential);
            $this->session->clearFormData('auth_retry');
            $this->flashSession->success(__('Login success'));
            return $this->redirect(url('account/redirect'));
        } catch (AuthException $e) {
            $this->flashSession->error($e->getMessage());
        } catch (\Exception $e) {
            Log::exception($e);
            $this->flashSession->error(__('Login failed'));
        }
        unset($credential['password']);
        $this->session->rememberFormData('auth_retry', $credential);
        return $this->redirect('account/login');
    }

    public function postRegister()
    {
        $this->rememberRedirectUrl();
        $credential = $this->request->getPost('register');
        try {
            $user = Auth::getInstance()->register($credential);
            $this->session->clearFormData('register_retry');
            if ($user->getData('confirmed'))
            return $this->redirect($this->session->get('redirect_url', url('account'), true));
        } catch (AuthException $e) {
            $this->flashSession->error($e->getMessage());
        } catch (\Exception $e) {
            Log::exception($e);
            $this->flashSession->error(__('Register failed'));
        }
        unset($credential['password'], $credential['confirm_password']);
        $this->session->rememberFormData('register_retry', $credential);
        return $this->redirect('account/register');
    }

    protected function rememberRedirectUrl()
    {
        if (($url = $this->request->get('redirect_url')) && isHttpUrl($url)) {
            $this->session->set('redirect_url', $url);
        }
        return $this;
    }
}
