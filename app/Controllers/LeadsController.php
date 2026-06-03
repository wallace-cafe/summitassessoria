<?php

namespace App\Controllers;

use App\Models\LandingPageModel;
use App\Models\LeadModel;

class LeadsController extends BaseController
{
    public function index()
    {
        $leadModel       = new LeadModel();
        $landingPageModel = new LandingPageModel();

        $search       = $this->request->getGet('search');
        $landingPageId = $this->request->getGet('landing_page');
        $sort         = $this->request->getGet('sort') ?? 'created_at';
        $order        = $this->request->getGet('order') ?? 'DESC';

        $allowedSorts = ['name', 'email', 'status', 'created_at'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $query = $leadModel;

        if ($search) {
            $query = $query->search($search);
        }

        if ($landingPageId) {
            $query = $query->filterByLandingPage((int) $landingPageId);
        }

        $leads = $query->orderBy($sort, $order)->findAll();

        // Attach landing page slug to each lead
        foreach ($leads as &$lead) {
            $page = $landingPageModel->find($lead['landing_page_id']);
            $lead['landing_page_slug'] = $page['slug'] ?? 'Unknown';
        }

        $data = [
            'leads'         => $leads,
            'landingPages'  => $landingPageModel->findAll(),
            'search'        => $search,
            'landingPageId' => $landingPageId,
            'sort'          => $sort,
            'order'         => $order,
        ];

        return view('leads/index', $data);
    }
}
