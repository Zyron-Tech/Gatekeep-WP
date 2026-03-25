<?php
defined( 'ABSPATH' ) || exit;

class ZLR_Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menu' ] );
        add_action( 'admin_init',            [ $this, 'handle_save' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Menu                                                                */
    /* ------------------------------------------------------------------ */

    public function register_menu() {
        add_options_page(
            __( 'Login Redirect', 'zyron-login-redirect' ),
            __( 'Login Redirect', 'zyron-login-redirect' ),
            'manage_options',
            'zlr-settings',
            [ $this, 'render_page' ]
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Assets                                                              */
    /* ------------------------------------------------------------------ */

    public function enqueue_assets( $hook ) {
        if ( 'settings_page_zlr-settings' !== $hook ) {
            return;
        }
        wp_enqueue_style(
            'zlr-admin',
            ZLR_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ZLR_VERSION
        );
        wp_enqueue_script(
            'zlr-admin',
            ZLR_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery' ],
            ZLR_VERSION,
            true
        );
        wp_localize_script( 'zlr-admin', 'ZLR', [
            'confirm_delete'        => __( 'Remove this redirect rule?', 'zyron-login-redirect' ),
            'confirm_delete_access' => __( 'Remove this access rule?', 'zyron-login-redirect' ),
        ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Save handler                                                        */
    /* ------------------------------------------------------------------ */

    public function handle_save() {
        if (
            ! isset( $_POST['zlr_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['zlr_nonce'] ) ), 'zlr_save_settings' )
        ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'zyron-login-redirect' ) );
        }

        $tab = isset( $_POST['zlr_tab'] ) ? sanitize_key( $_POST['zlr_tab'] ) : 'login';

        /* ── Tab: Login Redirect ── */
        if ( 'login' === $tab ) {
            $default = isset( $_POST['zlr_default_redirect'] )
                ? esc_url_raw( wp_unslash( $_POST['zlr_default_redirect'] ) )
                : admin_url();
            update_option( ZLR_DEFAULT_KEY, $default );

            $roles = isset( $_POST['zlr_role'] ) ? (array) $_POST['zlr_role'] : [];
            $urls  = isset( $_POST['zlr_url'] )  ? (array) $_POST['zlr_url']  : [];

            $rules = [];
            foreach ( $roles as $i => $role ) {
                $role = sanitize_text_field( $role );
                $url  = isset( $urls[ $i ] ) ? esc_url_raw( wp_unslash( $urls[ $i ] ) ) : '';
                if ( $role && $url ) {
                    $rules[ $role ] = $url;
                }
            }
            update_option( ZLR_OPTION_KEY, $rules );
        }

        /* ── Tab: Access Guard ── */
        if ( 'access' === $tab ) {
            $ag_roles   = isset( $_POST['zlr_ag_role'] )        ? (array) $_POST['zlr_ag_role']        : [];
            $ag_sources = isset( $_POST['zlr_ag_source'] )      ? (array) $_POST['zlr_ag_source']      : [];
            $ag_dests   = isset( $_POST['zlr_ag_destination'] ) ? (array) $_POST['zlr_ag_destination'] : [];
            $ag_labels  = isset( $_POST['zlr_ag_label'] )       ? (array) $_POST['zlr_ag_label']       : [];

            $access_rules = [];
            foreach ( $ag_roles as $i => $role ) {
                $role   = sanitize_text_field( $role );
                $source = isset( $ag_sources[ $i ] ) ? sanitize_text_field( wp_unslash( $ag_sources[ $i ] ) ) : '';
                $dest   = isset( $ag_dests[ $i ] )   ? esc_url_raw( wp_unslash( $ag_dests[ $i ] ) )            : '';
                $label  = isset( $ag_labels[ $i ] )  ? sanitize_text_field( wp_unslash( $ag_labels[ $i ] ) )   : '';

                if ( $role && $source && $dest ) {
                    $access_rules[] = [
                        'role'        => $role,
                        'source'      => $source,
                        'destination' => $dest,
                        'label'       => $label,
                    ];
                }
            }
            update_option( ZLR_ACCESS_KEY, $access_rules );
        }

        add_settings_error(
            'zlr_messages',
            'zlr_saved',
            __( 'Settings saved successfully.', 'zyron-login-redirect' ),
            'updated'
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Render page                                                         */
    /* ------------------------------------------------------------------ */

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab   = isset( $_GET['zlr_tab'] ) ? sanitize_key( $_GET['zlr_tab'] ) : 'login';
        $login_rules  = (array)  get_option( ZLR_OPTION_KEY, [] );
        $default_url  = (string) get_option( ZLR_DEFAULT_KEY, admin_url() );
        $access_rules = (array)  get_option( ZLR_ACCESS_KEY, [] );
        $all_roles    = wp_roles()->get_names();

        // Extra pseudo-roles for access guard
        $guard_roles = array_merge(
            [
                'logged_out' => __( 'Logged-Out Visitors', 'zyron-login-redirect' ),
                'any'        => __( 'Anyone (logged in or out)', 'zyron-login-redirect' ),
            ],
            $all_roles
        );

        settings_errors( 'zlr_messages' );
        ?>
        <div class="zlr-wrap">

            <!-- Header -->
            <div class="zlr-header">
                <div class="zlr-header__logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" fill="none" aria-hidden="true">
                        <rect width="36" height="36" rx="8" fill="#6C63FF"/>
                        <path d="M9 11h18l-9 7-9-7z" fill="#fff" opacity=".9"/>
                        <path d="M9 11l9 7 9-7v13H9V11z" fill="#fff" opacity=".2"/>
                        <path d="M18 18l9-7v13" stroke="#fff" stroke-width="1.5" stroke-linejoin="round" opacity=".7"/>
                        <circle cx="18" cy="26" r="2.5" fill="#A5F3FC"/>
                    </svg>
                    <span class="zlr-header__title">Zyron Login Redirect</span>
                    <span class="zlr-header__version">v<?php echo esc_html( ZLR_VERSION ); ?></span>
                </div>
                <a class="zlr-header__link" href="https://zyron-portfolio.vercel.app/" target="_blank" rel="noopener noreferrer">
                    zyron-portfolio.vercel.app ↗
                </a>
            </div>

            <!-- Tabs -->
            <div class="zlr-tabs" role="tablist">
                <a role="tab"
                   href="<?php echo esc_url( admin_url( 'options-general.php?page=zlr-settings&zlr_tab=login' ) ); ?>"
                   class="zlr-tab <?php echo 'login' === $active_tab ? 'zlr-tab--active' : ''; ?>"
                   aria-selected="<?php echo 'login' === $active_tab ? 'true' : 'false'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="15" height="15">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 011 1v12a1 1 0 11-2 0V4a1 1 0 011-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                    </svg>
                    Login Redirect
                </a>
                <a role="tab"
                   href="<?php echo esc_url( admin_url( 'options-general.php?page=zlr-settings&zlr_tab=access' ) ); ?>"
                   class="zlr-tab <?php echo 'access' === $active_tab ? 'zlr-tab--active' : ''; ?>"
                   aria-selected="<?php echo 'access' === $active_tab ? 'true' : 'false'; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="15" height="15">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                    </svg>
                    Access Guard
                    <span class="zlr-tab__badge"><?php echo count( $access_rules ); ?></span>
                </a>
            </div>

            <!-- ============================================================
                 TAB: LOGIN REDIRECT
            ============================================================ -->
            <?php if ( 'login' === $active_tab ) : ?>

            <p class="zlr-intro">
                Configure where each <strong>user role</strong> is redirected after a successful login —
                regardless of which login form they used.
            </p>

            <form method="post" action="" id="zlr-form">
                <?php wp_nonce_field( 'zlr_save_settings', 'zlr_nonce' ); ?>
                <input type="hidden" name="zlr_tab" value="login">

                <div class="zlr-card">
                    <div class="zlr-card__head">
                        <span class="zlr-badge zlr-badge--default">Default</span>
                        <h2 class="zlr-card__title">Fallback Redirect URL</h2>
                        <p class="zlr-card__desc">Used when no role-specific rule matches.</p>
                    </div>
                    <div class="zlr-card__body">
                        <div class="zlr-field zlr-field--single">
                            <label for="zlr_default_redirect" class="zlr-label">
                                <span class="zlr-label__icon">🌐</span> Redirect URL
                            </label>
                            <input type="url" id="zlr_default_redirect" name="zlr_default_redirect"
                                class="zlr-input" value="<?php echo esc_attr( $default_url ); ?>"
                                placeholder="https://example.com/dashboard" required />
                            <span class="zlr-hint">Leave as <code><?php echo esc_html( admin_url() ); ?></code> to keep the WordPress default.</span>
                        </div>
                    </div>
                </div>

                <div class="zlr-card">
                    <div class="zlr-card__head">
                        <span class="zlr-badge zlr-badge--roles">Role Rules</span>
                        <h2 class="zlr-card__title">Role-Based Redirect Rules</h2>
                        <p class="zlr-card__desc">These take priority over the fallback.</p>
                    </div>
                    <div class="zlr-card__body">
                        <div class="zlr-table-wrap">
                            <table class="zlr-table" id="zlr-rules-table">
                                <thead>
                                    <tr>
                                        <th class="zlr-th zlr-th--role">User Role</th>
                                        <th class="zlr-th zlr-th--url">Redirect To</th>
                                        <th class="zlr-th zlr-th--actions"></th>
                                    </tr>
                                </thead>
                                <tbody id="zlr-rules-body">
                                    <?php if ( ! empty( $login_rules ) ) : ?>
                                        <?php foreach ( $login_rules as $role => $url ) : ?>
                                            <tr class="zlr-row" data-row>
                                                <td class="zlr-td">
                                                    <select name="zlr_role[]" class="zlr-select" required>
                                                        <option value="">— Select role —</option>
                                                        <?php foreach ( $all_roles as $key => $label ) : ?>
                                                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $role ); ?>>
                                                                <?php echo esc_html( translate_user_role( $label ) ); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td class="zlr-td">
                                                    <input type="url" name="zlr_url[]" class="zlr-input"
                                                        value="<?php echo esc_attr( $url ); ?>"
                                                        placeholder="https://example.com/after-login" required />
                                                </td>
                                                <td class="zlr-td zlr-td--actions">
                                                    <?php echo $this->remove_btn(); // phpcs:ignore ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <?php echo $this->empty_row( 3 ); // phpcs:ignore ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" id="zlr-add-rule" class="zlr-btn-add">
                            <?php echo $this->plus_icon(); // phpcs:ignore ?> Add Rule
                        </button>
                    </div>
                </div>

                <?php $this->save_bar( 'Changes take effect immediately for all future logins.' ); ?>
            </form>

            <div class="zlr-card zlr-card--info">
                <div class="zlr-card__head">
                    <span class="zlr-badge zlr-badge--info">ℹ️ Info</span>
                    <h2 class="zlr-card__title">How Login Redirect Works</h2>
                </div>
                <div class="zlr-card__body zlr-how-grid">
                    <div class="zlr-how-item">
                        <span class="zlr-how-num">01</span>
                        <strong>User logs in</strong>
                        <p>Works with any form: WP default, WooCommerce, BuddyPress, Ultimate Member, custom themes, REST API.</p>
                    </div>
                    <div class="zlr-how-item">
                        <span class="zlr-how-num">02</span>
                        <strong>Role is checked</strong>
                        <p>The plugin reads the user's primary role and looks it up in your saved rules table above.</p>
                    </div>
                    <div class="zlr-how-item">
                        <span class="zlr-how-num">03</span>
                        <strong>Redirect fires</strong>
                        <p>Matching rule → sent there. No match → Fallback URL is used.</p>
                    </div>
                </div>
            </div>

            <!-- Row template for JS -->
            <template id="zlr-row-template">
                <tr class="zlr-row" data-row>
                    <td class="zlr-td">
                        <select name="zlr_role[]" class="zlr-select" required>
                            <option value="">— Select role —</option>
                            <?php foreach ( $all_roles as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>">
                                    <?php echo esc_html( translate_user_role( $label ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="zlr-td">
                        <input type="url" name="zlr_url[]" class="zlr-input"
                            placeholder="https://example.com/after-login" required />
                    </td>
                    <td class="zlr-td zlr-td--actions">
                        <?php echo $this->remove_btn(); // phpcs:ignore ?>
                    </td>
                </tr>
            </template>

            <?php endif; /* end login tab */ ?>


            <!-- ============================================================
                 TAB: ACCESS GUARD
            ============================================================ -->
            <?php if ( 'access' === $active_tab ) : ?>

            <p class="zlr-intro">
                Intercept any page visit and redirect the user based on their <strong>role</strong> and the
                <strong>URL they tried to access</strong>. Perfect for protecting pages, redirecting guests,
                or routing roles away from restricted areas.
            </p>

            <form method="post" action="" id="zlr-access-form">
                <?php wp_nonce_field( 'zlr_save_settings', 'zlr_nonce' ); ?>
                <input type="hidden" name="zlr_tab" value="access">

                <div class="zlr-card">
                    <div class="zlr-card__head">
                        <span class="zlr-badge zlr-badge--guard">Access Guard</span>
                        <h2 class="zlr-card__title">Role + URL → Redirect Rules</h2>
                        <p class="zlr-card__desc">Rules are evaluated top-to-bottom; first match wins.</p>
                    </div>
                    <div class="zlr-card__body">

                        <div class="zlr-pattern-ref">
                            <span class="zlr-pattern-ref__title">URL Pattern guide:</span>
                            <span class="zlr-chip"><code>/shop/</code> exact path</span>
                            <span class="zlr-chip"><code>/shop/*</code> path wildcard</span>
                            <span class="zlr-chip"><code>https://example.com/vip/</code> full URL</span>
                            <span class="zlr-chip"><code>https://example.com/vip/*</code> full URL wildcard</span>
                        </div>

                        <div class="zlr-table-wrap">
                            <table class="zlr-table zlr-table--access" id="zlr-access-table">
                                <thead>
                                    <tr>
                                        <th class="zlr-th" style="width:17%">Label <span class="zlr-th-opt">(optional)</span></th>
                                        <th class="zlr-th" style="width:20%">Role / Visitor Type</th>
                                        <th class="zlr-th" style="width:24%">If they try to visit…</th>
                                        <th class="zlr-th" style="width:31%">Send them to…</th>
                                        <th class="zlr-th" style="width:8%"></th>
                                    </tr>
                                </thead>
                                <tbody id="zlr-access-body">
                                    <?php if ( ! empty( $access_rules ) ) : ?>
                                        <?php foreach ( $access_rules as $rule ) : ?>
                                            <tr class="zlr-row" data-access-row>
                                                <td class="zlr-td">
                                                    <input type="text" name="zlr_ag_label[]" class="zlr-input zlr-input--sm"
                                                        value="<?php echo esc_attr( $rule['label'] ?? '' ); ?>"
                                                        placeholder="e.g. Block guests" />
                                                </td>
                                                <td class="zlr-td">
                                                    <select name="zlr_ag_role[]" class="zlr-select" required>
                                                        <option value="">— Role —</option>
                                                        <?php foreach ( $guard_roles as $key => $grlabel ) : ?>
                                                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $rule['role'] ?? '' ); ?>>
                                                                <?php echo esc_html( $grlabel ); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td class="zlr-td">
                                                    <input type="text" name="zlr_ag_source[]" class="zlr-input zlr-input--sm"
                                                        value="<?php echo esc_attr( $rule['source'] ?? '' ); ?>"
                                                        placeholder="/members-only/*" required />
                                                </td>
                                                <td class="zlr-td">
                                                    <input type="url" name="zlr_ag_destination[]" class="zlr-input zlr-input--sm"
                                                        value="<?php echo esc_attr( $rule['destination'] ?? '' ); ?>"
                                                        placeholder="https://example.com/login" required />
                                                </td>
                                                <td class="zlr-td zlr-td--actions">
                                                    <?php echo $this->remove_btn( 'data-access-remove' ); // phpcs:ignore ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <?php echo $this->empty_row( 5, 'zlr-access-empty' ); // phpcs:ignore ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" id="zlr-add-access-rule" class="zlr-btn-add">
                            <?php echo $this->plus_icon(); // phpcs:ignore ?> Add Access Rule
                        </button>
                    </div>
                </div>

                <?php $this->save_bar( 'Rules are applied site-wide on every page load.' ); ?>
            </form>

            <!-- How it works -->
            <div class="zlr-card zlr-card--info">
                <div class="zlr-card__head">
                    <span class="zlr-badge zlr-badge--info">ℹ️ Info</span>
                    <h2 class="zlr-card__title">How Access Guard Works</h2>
                </div>
                <div class="zlr-card__body zlr-how-grid">
                    <div class="zlr-how-item">
                        <span class="zlr-how-num">01</span>
                        <strong>User visits a URL</strong>
                        <p>On every non-admin page load, the plugin checks the current URL against all your rules.</p>
                    </div>
                    <div class="zlr-how-item">
                        <span class="zlr-how-num">02</span>
                        <strong>Role + URL matched</strong>
                        <p>If the visitor's role matches AND the URL pattern matches, the first matching rule fires.</p>
                    </div>
                    <div class="zlr-how-item">
                        <span class="zlr-how-num">03</span>
                        <strong>Instant redirect</strong>
                        <p>The visitor is sent to the destination URL. Loop detection prevents infinite redirects.</p>
                    </div>
                    <div class="zlr-how-item">
                        <span class="zlr-how-num">04</span>
                        <strong>No match = no change</strong>
                        <p>If no rule matches, the user stays on the page normally.</p>
                    </div>
                </div>

                <div class="zlr-examples-section">
                    <p class="zlr-examples-title">Common use cases:</p>
                    <div class="zlr-examples-grid">
                        <div class="zlr-example">
                            <span class="zlr-example__icon">🔒</span>
                            <div>
                                <strong>Protect premium pages</strong>
                                <p>Subscribers visit <code>/vip/*</code> → sent to <code>/upgrade/</code></p>
                            </div>
                        </div>
                        <div class="zlr-example">
                            <span class="zlr-example__icon">👤</span>
                            <div>
                                <strong>Guest wall</strong>
                                <p>Logged-out visitors visit <code>/dashboard/</code> → sent to <code>/login/</code></p>
                            </div>
                        </div>
                        <div class="zlr-example">
                            <span class="zlr-example__icon">🛒</span>
                            <div>
                                <strong>Alternate checkout for role</strong>
                                <p>Wholesale users visit <code>/checkout/</code> → sent to <code>/wholesale-order/</code></p>
                            </div>
                        </div>
                        <div class="zlr-example">
                            <span class="zlr-example__icon">🚫</span>
                            <div>
                                <strong>Block wp-admin for subscribers</strong>
                                <p>Subscribers visit <code>/wp-admin/*</code> → sent to <code>/</code></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Access Guard row template for JS -->
            <template id="zlr-access-row-template">
                <tr class="zlr-row" data-access-row>
                    <td class="zlr-td">
                        <input type="text" name="zlr_ag_label[]" class="zlr-input zlr-input--sm" placeholder="e.g. Block guests" />
                    </td>
                    <td class="zlr-td">
                        <select name="zlr_ag_role[]" class="zlr-select" required>
                            <option value="">— Role —</option>
                            <?php foreach ( $guard_roles as $key => $grlabel ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>">
                                    <?php echo esc_html( $grlabel ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="zlr-td">
                        <input type="text" name="zlr_ag_source[]" class="zlr-input zlr-input--sm" placeholder="/members-only/*" required />
                    </td>
                    <td class="zlr-td">
                        <input type="url" name="zlr_ag_destination[]" class="zlr-input zlr-input--sm" placeholder="https://example.com/login" required />
                    </td>
                    <td class="zlr-td zlr-td--actions">
                        <?php echo $this->remove_btn( 'data-access-remove' ); // phpcs:ignore ?>
                    </td>
                </tr>
            </template>

            <?php endif; /* end access tab */ ?>

            <!-- Footer -->
            <div class="zlr-footer">
                Built by <a href="https://zyron-portfolio.vercel.app/" target="_blank" rel="noopener noreferrer">Zyron Tech</a>
                &mdash; Zyron Login Redirect v<?php echo esc_html( ZLR_VERSION ); ?>
            </div>

        </div><!-- .zlr-wrap -->
        <?php
    }

    /* ------------------------------------------------------------------ */
    /*  Reusable HTML snippets                                              */
    /* ------------------------------------------------------------------ */

    private function remove_btn( string $extra_attr = 'data-remove' ): string {
        return sprintf(
            '<button type="button" class="zlr-btn-remove" %s title="%s">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </button>',
            esc_attr( $extra_attr ),
            esc_attr__( 'Remove rule', 'zyron-login-redirect' )
        );
    }

    private function empty_row( int $cols = 3, string $id = 'zlr-empty-row' ): string {
        $label = $cols >= 5 ? 'Access Rule' : 'Rule';
        return sprintf(
            '<tr class="zlr-row zlr-row--empty" id="%s">
                <td colspan="%d">
                    <div class="zlr-empty">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" width="48" height="48">
                            <circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2" stroke-dasharray="4 3"/>
                            <path d="M24 16v8M24 30v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <p>No rules yet. Click <strong>+ Add %s</strong> to get started.</p>
                    </div>
                </td>
            </tr>',
            esc_attr( $id ),
            (int) $cols,
            esc_html( $label )
        );
    }

    private function plus_icon(): string {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
        </svg>';
    }

    private function save_bar( string $note ): void {
        echo '<div class="zlr-actions">
            <button type="submit" class="zlr-btn-save">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                    <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h-2v5.586l-1.293-1.293z"/>
                    <path d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"/>
                </svg>
                Save Settings
            </button>
            <span class="zlr-actions__note">' . esc_html( $note ) . '</span>
        </div>';
    }
}
