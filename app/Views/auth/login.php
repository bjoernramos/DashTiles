<!doctype html>
<html lang="<?= esc(service('request')->getLocale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc(lang('App.brand')) ?> • <?= esc(lang('App.pages.auth.login')) ?></title>
    <?= view('partials/bootstrap_head') ?>
    <base href="<?= htmlspecialchars($basePath ?? '/toolpages', ENT_QUOTES) ?>/">

    <style>
        /* ---- Minimal Modern Light Theme ---- */
        :root {
            --bg: #f7f7f8;
            --card-bg: #ffffff;
            --text: #222;
            --text-muted: #666;
            --border: #e3e3e3;
            --primary: #0d6efd;
        }

        /* ---- Auto Dark Mode ---- */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #111214;
                --card-bg: #1b1c1f;
                --text: #e7e7e7;
                --text-muted: #9ea0a5;
                --border: #2b2c2f;
                --primary: #4d93ff;
            }
            body {
                color-scheme: dark;
            }
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
        }

        .card {
            background: var(--card-bg) !important;
            border: 1px solid var(--border) !important;
            border-radius: 14px;
        }

        .form-control {
            background: var(--card-bg);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 10px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
        }

        .nav-tabs .nav-link {
            border: none;
            padding: .6rem 1rem;
            color: var(--text-muted);
        }

        .nav-tabs .nav-link.active {
            font-weight: 600;
            color: var(--text);
            border-bottom: 2px solid var(--primary);
        }

        .login-title {
            font-weight: 600;
            font-size: 1.4rem;
        }

        .login-subtitle {
            font-size: .85rem;
            color: var(--text-muted);
        }

        .btn-primary {
            border-radius: 10px;
            padding: .75rem;
            font-weight: 500;
            background-color: var(--primary);
        }
    </style>

</head>

<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">

            <div class="card shadow-sm p-4">

                <div class="text-center mb-4">
                    <div class="login-title"><?= esc(lang('App.pages.auth.login')) ?></div>
<!--                    <div class="login-subtitle">--><?php //= esc(lang('App.pages.auth.subtitle') ?? '') ?><!--</div>-->
                </div>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <ul class="nav nav-tabs justify-content-center" id="loginTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active"
                                id="localdb-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#localdb"
                                type="button"
                                role="tab">
                            <?= esc(lang('App.pages.auth.tabs.standard')) ?>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="ldap-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#ldap"
                                type="button"
                                role="tab">
                            LDAP
                        </button>
                    </li>
                </ul>

                <div class="tab-content pt-4">

                    <!-- Local Login -->
                    <div class="tab-pane fade show active" id="localdb" role="tabpanel">
                        <form method="post" action="login/local">
                            <div class="mb-3">
                                <label for="username_local" class="form-label">
                                    <?= esc(lang('App.pages.users.username')) ?>
                                </label>
                                <input id="username_local" name="username" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="password_local" class="form-label">
                                    <?= esc(lang('App.pages.auth.password')) ?>
                                </label>
                                <input id="password_local" name="password" type="password" class="form-control" required>
                            </div>

                            <button class="btn btn-primary w-100" type="submit">
                                <?= esc(lang('App.pages.auth.login_btn')) ?>
                            </button>
                        </form>
                    </div>

                    <!-- LDAP Login -->
                    <div class="tab-pane fade" id="ldap" role="tabpanel">
                        <form method="post" action="login/ldap">
                            <div class="mb-3">
                                <label for="username_ldap" class="form-label">
                                    <?= esc(lang('App.pages.users.username')) ?>
                                </label>
                                <input id="username_ldap" name="username" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label for="password_ldap" class="form-label">
                                    <?= esc(lang('App.pages.auth.password')) ?>
                                </label>
                                <input id="password_ldap" name="password" type="password" class="form-control" required>
                            </div>

                            <button class="btn btn-primary w-100" type="submit">
                                <?= esc(lang('App.pages.auth.login_with_ldap')) ?>
                            </button>
                        </form>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

<?= view('partials/bootstrap_scripts') ?>
</body>
</html>