<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    public function login()
    {
        if ($this->session->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function authenticate()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please fill in all fields.');
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $userModel = new UserModel();
        $user      = $userModel->findByUsername($username);

        if (! $user || ! password_verify($password, $user['password'])) {
            log_message('warning', 'Failed login attempt for username: {username} from IP: {ip}', [
                'username' => $username,
                'ip'       => $this->request->getIPAddress(),
            ]);

            return redirect()->back()->withInput()->with('error', 'Invalid username or password.');
        }

        $this->session->regenerate(true);
        $this->session->set([
            'isLoggedIn' => true,
            'user_id'    => $user['id'],
            'username'   => $user['username'],
        ]);

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        $this->session->destroy();

        return redirect()->to('/login');
    }
}
