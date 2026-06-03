<?php

namespace App\Controllers\Api;

use App\Models\LandingPageModel;
use App\Models\LeadModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class LpController extends Controller
{
    public function list(): ResponseInterface
    {
        $pages = (new LandingPageModel())
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $data = array_map(static fn($p) => [
            'id'         => $p['id'],
            'title'      => $p['title'],
            'slug'       => $p['slug'],
            'created_at' => $p['created_at'],
        ], $pages);

        return $this->response->setJSON(['data' => $data, 'meta' => (object) [], 'errors' => null]);
    }

    public function allLeads(): ResponseInterface
    {
        // All leads across every landing page. Each lead carries its origin
        // landing page slug and title for easy identification. A LEFT join keeps
        // leads whose landing page was removed (slug/title come back as null).
        $leads = (new LeadModel())
            ->select('leads.*, landing_pages.slug AS landing_page_slug, landing_pages.title AS landing_page_title')
            ->join('landing_pages', 'landing_pages.id = leads.landing_page_id', 'left')
            ->orderBy('leads.created_at', 'DESC')
            ->findAll();

        return $this->response->setJSON(['data' => $leads, 'meta' => (object) [], 'errors' => null]);
    }

    public function leads($id): ResponseInterface
    {
        $page = (new LandingPageModel())->find((int) $id);

        if (! $page) {
            return $this->response
                ->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                ->setJSON(['data' => null, 'meta' => (object) [], 'errors' => 'Landing page not found']);
        }

        $leads = (new LeadModel())
            ->filterByLandingPage((int) $page['id'])
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->response->setJSON(['data' => $leads, 'meta' => (object) [], 'errors' => null]);
    }
}
