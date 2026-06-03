<?php

namespace App\Controllers;

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

        $username = (string) $this->request->getPost('username');
        $password = (string) $this->request->getPost('password');

        // Admin credentials are derived, not stored: the user is always "admin"
        // and the password is "summit" + the current day and month (Brazil time),
        // e.g. "summit0306" on June 3rd. No database user is required.
        $today            = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $expectedPassword = 'summit' . $today->format('dm');

        if ($username !== 'admin' || ! hash_equals($expectedPassword, $password)) {
            log_message('warning', 'Failed login attempt for username: {username} from IP: {ip}', [
                'username' => $username,
                'ip'       => $this->request->getIPAddress(),
            ]);

            return redirect()->back()->withInput()->with('error', 'Invalid username or password.');
        }

        $this->session->regenerate(true);
        $this->session->set([
            'isLoggedIn' => true,
            'user_id'    => 0,
            'username'   => 'admin',
        ]);

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        $this->session->destroy();

        return redirect()->to('/summit-admin');
    }
}
