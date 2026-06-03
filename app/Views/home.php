<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summit</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime(FCPATH . 'assets/css/style.css') ?>">
    <style>
        /* Muted, infinite-loop background video covering the whole viewport */
        .bg-video {
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -2;
            pointer-events: none;
        }

        /* Dark tint over the video so the white logo / text stay legible */
        .bg-overlay {
            position: fixed;
            inset: 0;
            background: rgba(11, 14, 17, 0.6);
            z-index: -1;
            pointer-events: none;
        }

        .home-hero {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            text-align: center;
        }

        .home-hero__logo {
            width: 340px;
            max-width: 80%;
            height: auto;
            margin-bottom: 2.5rem;
        }

        .home-hero__text {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.7;
            max-width: 560px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
        }

        .home-hero__text a {
            color: var(--primary);
            word-break: break-word;
        }
    </style>
</head>
<body>
    <video class="bg-video" autoplay muted loop playsinline preload="auto" aria-hidden="true" tabindex="-1">
        <source src="/assets/video/video-background-hero.mp4" type="video/mp4">
    </video>
    <div class="bg-overlay"></div>

    <main class="home-hero">
        <img src="/assets/img/logotipo-summit.png" alt="Summit Assessoria" class="home-hero__logo">
        <p class="home-hero__text">
            Para saber mais sobre nossos serviços acesse nosso site institucional:
            <a href="https://summitconsult.com.br" target="_blank" rel="noopener">https://summitconsult.com.br</a>
        </p>
    </main>
</body>
</html>
