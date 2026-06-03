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

        // Two derived/hardcoded credential sets (no database user):
        //  - "admin"   : password "summit" + current day+month, Brazil time
        //                (e.g. "summit0306" on June 3rd). Standard access.
        //  - "wallace" : a fixed owner password. This is the ONLY login allowed
        //                to create new landing pages (drives can_create_pages).
        $today         = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
        $adminPassword = 'summit' . $today->format('dm');

        $authedUser     = null;
        $canCreatePages = false;

        if ($username === 'admin' && hash_equals($adminPassword, $password)) {
            $authedUser = 'admin';
        } elseif ($username === 'wallace' && hash_equals('#Cimo2820!!', $password)) {
            $authedUser     = 'wallace';
            $canCreatePages = true;
        }

        if ($authedUser === null) {
            log_message('warning', 'Failed login attempt for username: {username} from IP: {ip}', [
                'username' => $username,
                'ip'       => $this->request->getIPAddress(),
            ]);

            return redirect()->back()->withInput()->with('error', 'Invalid username or password.');
        }

        $this->session->regenerate(true);
        $this->session->set([
            'isLoggedIn'       => true,
            'user_id'          => 0,
            'username'         => $authedUser,
            'can_create_pages' => $canCreatePages,
        ]);

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        $this->session->destroy();

        return redirect()->to('/summit-admin');
    }
}
