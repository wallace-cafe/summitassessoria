<?php

namespace App\Controllers;

use App\Models\LandingPageModel;
use App\Models\LeadModel;

class PublicController extends BaseController
{
    public function show($slug)
    {
        $model = new LandingPageModel();
        $page  = $model->findBySlug($slug);

        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $filePath = WRITEPATH . ($page['file_path'] ?? '') . '/index.html';

        if (! is_file($filePath)) {
            log_message('error', '[landing_page.missing_index_html slug=' . $slug . ']');
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $html = file_get_contents($filePath);

        // Replace the GTM placeholder (e.g. GTM-XXXXXX or GTM-XXXXXXX) with the
        // actual GTM ID. Match any run of X's so the whole placeholder is swapped
        // out, leaving no leftover characters regardless of how many X's the
        // template uses. A callback is used so the replacement is taken literally
        // (no `$`/backreference interpretation in the GTM ID).
        if (! empty($page['gtm_id'])) {
            $gtmId = $page['gtm_id'];
            $html  = preg_replace_callback(
                '/GTM-X+/',
                static fn (): string => $gtmId,
                $html
            );
        }

        $baseTag = '<base href="/p/' . $slug . '/">';
        $html = str_replace('<head>', "<head>\n    " . $baseTag, $html);

        // Add preview badges if in edit mode (preview frame)
        $isPreview = $this->request->getGet('preview') === '1';
        if ($isPreview) {
            helper('block_editor');
            $html = inject_block_badges($html);
            
            // Add CSS for badges
            $badgeStyles = '<style>
                .block-badge {
                    position: relative;
                    display: block;
                }
                .block-badge::before {
                    content: attr(data-block-label);
                    position: absolute;
                    top: 4px;
                    left: 4px;
                    background: #ef4444;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 3px;
                    font-size: 11px;
                    font-weight: bold;
                    z-index: 10000;
                    line-height: 1.4;
                }
                .block-badge::after {
                    content: "";
                    position: absolute;
                    inset: 0;
                    border: 3px dashed rgba(239, 68, 68, 0.5);
                    pointer-events: none;
                    z-index: 9999;
                }
            </style>';
            $html = str_replace('</head>', $badgeStyles . '</head>', $html);
        }

        // Inject landing_page_id into contact-form
        if (str_contains($html, 'id="contact-form"')) {
            $html = str_replace(
                '<form id="contact-form"',
                '<form id="contact-form" data-page-id="' . $page['id'] . '">',
                $html
            );
        } else {
            log_message('warning', '[landing_page.no_contact_form slug=' . $slug . ']');
        }

        // Replace form-success with checkmark message + WhatsApp button outside
        $successHtml = '<div class="form-success" id="form-success">'
            . '<p style="text-align:center;font-weight:500;line-height:1.6;">'
            . 'O primeiro passo foi realizado com sucesso!<br>'
            . 'Agora você pode prosseguir para um atendimento humanizado.'
            . '</p>'
            . '</div>'
            . '<a href="https://wa.me/5511997065097" class="btn-whatsapp" target="_blank" rel="noopener"'
            . ' id="whatsapp-lead-link" style="display:none;">'
            . '<svg viewBox="0 0 24 24" style="width:1.2rem;height:1.2rem;fill:#fff;">'
            . '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>'
            . '</svg>'
            . ' Fale com nossos especialistas'
            . '</a>';

        $html = preg_replace(
            '/<div\b[^>]*\bid\s*=\s*["\']form-success["\'][^>]*>.*?<\/div>/is',
            $successHtml,
            $html,
            1
        );

        // Inject lead submission handler (overrides app.js default behavior)
        $handlerScript = '<script>'
            . 'document.addEventListener("DOMContentLoaded",function(){'
            . 'var form=document.getElementById("contact-form");'
            . 'if(!form)return;'
            . 'var successDiv=document.getElementById("form-success");'
            . 'if(!successDiv)return;'
            . 'var newForm=form.cloneNode(true);'
            . 'form.parentNode.replaceChild(newForm,form);'
            . 'form=newForm;'
            . 'form.addEventListener("submit",async function(e){'
            . 'e.preventDefault();'
            . 'function val(n){var el=form.querySelector("[name="+n+"]");return el?el.value:""}'
            . 'var data=new URLSearchParams();'
            . 'data.append("name",val("nome")||val("name"));'
            . 'data.append("email",val("email"));'
            . 'data.append("phone",val("telefone")||val("phone"));'
            . 'data.append("message",val("mensagem")||val("message"));'
            . 'data.append("landing_page_id",form.getAttribute("data-page-id")||"' . $page['id'] . '");'
            . 'try{'
            . 'var resp=await fetch("/p/' . $slug . '/lead",{'
            . 'method:"POST",'
            . 'headers:{"Content-Type":"application/x-www-form-urlencoded","X-Requested-With":"XMLHttpRequest"},'
            . 'body:data'
            . '});'
            . 'if(resp.ok){'
            . 'var result=await resp.json();'
            . 'var nome=result.name||val("nome")||val("name")||"";'
            . 'form.style.display="none";'
            . 'var wa=document.getElementById("whatsapp-lead-link");'
            . 'if(wa){'
            . 'wa.href="https://wa.me/5511997065097?text="+encodeURIComponent("Ol\u00e1, eu me chamo "+nome+" e gostaria de saber mais sobre os servi\u00e7os de negocia\u00e7\u00e3o de d\u00edvidas e recupera\u00e7\u00e3o judicial.")'
            . '}'
                . 'successDiv.classList.add("show");'
            . 'if(wa)wa.style.display="inline-flex";'
            . '}'
            . '}catch(err){console.error(err)}'
            . '});'
            . '});'
            . '</script>';

        $html = str_replace('</body>', $handlerScript . "\n</body>", $html);

        return view('public/landing_page', ['htmlContent' => $html]);
    }

    public function asset(string $slug, string ...$parts)
    {
        $model = new LandingPageModel();
        $page  = $model->findBySlug($slug);

        if (! $page || ! $page['file_path']) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $filepath = implode('/', $parts);
        $fullPath = WRITEPATH . $page['file_path'] . '/' . $filepath;
        $realPath = realpath($fullPath);
        $basePath = realpath(WRITEPATH . $page['file_path']);

        if (! $realPath || ! $basePath || ! str_starts_with($realPath, $basePath) || ! is_file($realPath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mimeMap = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
            'gif'  => 'image/gif',
            'html' => 'text/html',
            'mp4'  => 'video/mp4',
            'webm' => 'video/webm',
            'ogg'  => 'video/ogg',
        ];

        $ext  = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $mime = $mimeMap[$ext] ?? (mime_content_type($realPath) ?: 'application/octet-stream');

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->setBody(file_get_contents($realPath));
    }

    public function storeLead($slug)
    {
        $landingPageModel = new LandingPageModel();
        $page             = $landingPageModel->findBySlug($slug);

        if (! $page) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $rules = [
            'name'  => 'required',
            'email' => 'required|valid_email',
        ];

        if (! $this->validate($rules)) {
            if ($this->isAjaxRequest()) {
                return $this->response->setJSON([
                    'success' => false,
                    'errors'  => $this->validator->getErrors(),
                ]);
            }
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $leadModel = new LeadModel();
        $leadModel->insert([
            'landing_page_id' => $page['id'],
            'name'            => $this->request->getPost('name'),
            'email'           => $this->request->getPost('email'),
            'phone'           => $this->request->getPost('phone'),
            'message'         => $this->request->getPost('message'),
            'status'          => 'New',
        ]);

        if ($this->isAjaxRequest()) {
            return $this->response->setJSON([
                'success' => true,
                'name'    => $this->request->getPost('name'),
            ]);
        }

        return redirect()->to("/p/{$slug}")->with('message', 'Thank you! Your submission has been received.');
    }

    private function isAjaxRequest(): bool
    {
        $header = $this->request->getHeader('X-Requested-With');
        return $header && $header->getValue() === 'XMLHttpRequest';
    }
}
