<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to CodeIgniter 4!</title>
    <meta name="description" content="The small framework with powerful features">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="<?= base_url('/favicon.ico'); ?>">

    <!-- STYLES -->
    <style>
        * {
            transition: background-color 300ms ease, color 300ms ease;
        }
        *:focus {
            background-color: rgba(221, 72, 20, .2);
            outline: none;
        }
        html, body {
            color: #212529;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
            font-size: 16px;
            margin: 0;
            padding: 0;
            text-rendering: optimizeLegibility;
        }
        header {
            background-color: #f7f8f9;
            padding: 0.4rem 0 0;
        }
        .menu {
            padding: 0.4rem 2rem;
        }
        header ul {
            border-bottom: 1px solid #f2f2f2;
            list-style-type: none;
            margin: 0;
            padding: 0;
            text-align: right;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        header li {
            display: inline-block;
        }
        header li a {
            color: rgba(0, 0, 0, .5);
            display: block;
            padding: 10px;
            text-decoration: none;
            font-weight: bold;
        }
        header li.menu-item a:hover {
            background-color: rgba(221, 72, 20, .2);
            color: rgba(221, 72, 20, 1);
        }
        header .logo {
            float: left;
            padding: 10px;
        }
        header .menu-toggle {
            display: none;
            font-size: 2rem;
            font-weight: bold;
        }
        .heroe {
            text-align: center;
            padding: 2rem;
        }
        section {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2.5rem;
        }
        section h1 {
            margin-bottom: 1.5rem;
        }
        .further {
            background-color: #f7f8f9;
            padding: 2rem;
            text-align: center;
        }
        footer {
            background-color: #dd4814;
            text-align: center;
            color: white;
            padding: 2rem;
        }
        footer .copyrights {
            background-color: #3e3e3e;
            color: #c8c8c8;
            padding: 1rem;
        }
        @media (max-width: 629px) {
            header .menu-toggle {
                display: block;
                padding: 10px;
            }
            .menu-item {
                display: none;
            }
            .menu-item.show {
                display: block;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="menu">
        <ul>
            <li class="logo">
                <a href="https://codeigniter.com" target="_blank">
                    <img src="<?= base_url('/ci-logo.png'); ?>" alt="CodeIgniter Logo" height="40">
                </a>
            </li>
            <li class="menu-toggle">
                <button id="menuToggle">&#9776;</button>
            </li>
            <li class="menu-item"><a href="#">Home</a></li>
            <li class="menu-item"><a href="https://codeigniter.com/user_guide/" target="_blank">Docs</a></li>
            <li class="menu-item"><a href="https://forum.codeigniter.com/" target="_blank">Community</a></li>
            <li class="menu-item"><a href="https://codeigniter.com/contribute" target="_blank">Contribute</a></li>
        </ul>
    </div>
</header>

<!-- HERO SECTION -->
<div class="heroe">
    <h1>Welcome to CodeIgniter <?= CodeIgniter\CodeIgniter::CI_VERSION ?></h1>
    <h2>The small framework with powerful features</h2>
</div>

<!-- CONTENT SECTION -->
<section>
    <h1>About this page</h1>
    <p>The page you are looking at is being generated dynamically by CodeIgniter.</p>
    <p>You can edit this page in:</p>
    <pre><code>app/Views/welcome_message.php</code></pre>
    <p>The corresponding controller for this page is located at:</p>
    <pre><code>app/Controllers/Home.php</code></pre>
</section>

<!-- FURTHER INFORMATION -->
<div class="further">
    <h1>Go Further</h1>
    <p>Check the <a href="https://codeigniter.com/user_guide/" target="_blank">User Guide</a>!</p>
    <p>Join the community in <a href="https://forum.codeigniter.com/" target="_blank">CodeIgniter's forum</a> or <a href="https://join.slack.com/t/codeigniterchat/shared_invite/" target="_blank">chat on Slack</a>!</p>
</div>

<!-- FOOTER -->
<footer>
    <p>Page rendered in {elapsed_time} seconds using {memory_usage} MB of memory.</p>
    <p>Environment: <?= esc(ENVIRONMENT) ?></p>
</footer>

<div class="copyrights">
    <p>&copy; <?= date('Y') ?> CodeIgniter Foundation. CodeIgniter is open-source software licensed under the MIT License.</p>
</div>

<!-- SCRIPTS -->
<script>
    document.getElementById("menuToggle").addEventListener("click", function () {
        let items = document.querySelectorAll(".menu-item");
        items.forEach(item => item.classList.toggle("show"));
    });
</script>

</body>
</html>
