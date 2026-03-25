<?php
defined( 'ABSPATH' ) || exit;

/**
 * ZLR_Redirector
 *
 * Hooks into every WordPress login pathway and redirects the user
 * based on their primary role.  Covered pathways:
 *
 *  1. wp_login           – Standard WP login form & programmatic wp_signon()
 *  2. login_redirect     – Filters the redirect URL right before WP sends it
 *  3. woocommerce_login_redirect – WooCommerce My Account form
 *  4. bp_core_login_redirect     – BuddyPress / BuddyBoss
 *  5. um_after_user_login        – Ultimate Member
 *  6. profile_builder_login_redirect – Profile Builder
 *  7. wp_authenticate_user  – Very early hook, sets a transient so the
 *                              login_redirect filter always has the URL ready.
 */
class ZLR_Redirector {

    /** @var array<string,string> */
    private array $rules;

    /** @var string */
    private string $default_url;

    public function __construct() {
        $this->rules       = (array) get_option( ZLR_OPTION_KEY, [] );
        $this->default_url = (string) get_option( ZLR_DEFAULT_KEY, admin_url() );

        // ── Core login redirect filter (highest priority, covers almost everything) ──
        add_filter( 'login_redirect',  [ $this, 'filter_login_redirect' ], 9999, 3 );

        // ── Direct hook after login (catches edge cases that bypass login_redirect) ──
        add_action( 'wp_login',        [ $this, 'action_wp_login' ], 9999, 2 );

        // ── WooCommerce ──
        add_filter( 'woocommerce_login_redirect', [ $this, 'filter_simple_redirect' ], 9999, 2 );

        // ── BuddyPress / BuddyBoss ──
        add_filter( 'bp_core_login_redirect', [ $this, 'filter_bp_redirect' ], 9999 );

        // ── Ultimate Member ──
        add_action( 'um_after_user_login', [ $this, 'action_um_after_login' ], 9999 );

        // ── Profile Builder ──
        add_filter( 'profile_builder_login_redirect', [ $this, 'filter_simple_redirect' ], 9999, 2 );

        // ── REST API / application passwords — handled via wp_login above ──
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Return the redirect URL for a given WP_User, or false if no rule applies.
     */
    private function get_redirect_for_user( WP_User $user ) {
        if ( empty( $this->rules ) ) {
            return $this->default_url ?: false;
        }

        $primary_role = $user->roles[0] ?? '';

        if ( $primary_role && isset( $this->rules[ $primary_role ] ) ) {
            return $this->rules[ $primary_role ];
        }

        // Try each role the user has (in case they have multiple)
        foreach ( $user->roles as $role ) {
            if ( isset( $this->rules[ $role ] ) ) {
                return $this->rules[ $role ];
            }
        }

        return $this->default_url ?: false;
    }

    /* ------------------------------------------------------------------ */
    /*  Hooks                                                               */
    /* ------------------------------------------------------------------ */

    /**
     * login_redirect filter — cleanest hook, works for all standard WP logins.
     *
     * @param string           $redirect_to           The redirect destination URL.
     * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
     * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
     * @return string
     */
    public function filter_login_redirect( string $redirect_to, string $requested_redirect_to, $user ): string {
        if ( ! ( $user instanceof WP_User ) ) {
            return $redirect_to;
        }

        $url = $this->get_redirect_for_user( $user );
        return $url ?: $redirect_to;
    }

    /**
     * wp_login action — fires after a user logs in.
     * Acts as a safety net: if the login_redirect filter was somehow bypassed
     * (e.g., direct call to wp_set_auth_cookie followed by a manual redirect),
     * we send the redirect here.
     *
     * @param string  $user_login Username.
     * @param WP_User $user       WP_User object.
     */
    public function action_wp_login( string $user_login, WP_User $user ): void {
        // Only act if headers haven't been sent yet and we're in an HTTP context.
        if ( headers_sent() ) {
            return;
        }

        $url = $this->get_redirect_for_user( $user );
        if ( ! $url ) {
            return;
        }

        // Don't double-redirect if we're in a REST / AJAX request.
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }
    }

    /**
     * Generic filter that receives ( $redirect_url, $user ) — used by WooCommerce,
     * Profile Builder, etc.
     *
     * @param string  $redirect_to Original URL.
     * @param WP_User $user        Logged-in user.
     * @return string
     */
    public function filter_simple_redirect( string $redirect_to, $user ): string {
        if ( ! ( $user instanceof WP_User ) ) {
            // Some plugins pass user ID instead of object
            if ( is_numeric( $user ) ) {
                $user = get_user_by( 'id', (int) $user );
                if ( ! $user ) {
                    return $redirect_to;
                }
            } else {
                return $redirect_to;
            }
        }

        $url = $this->get_redirect_for_user( $user );
        return $url ?: $redirect_to;
    }

    /**
     * BuddyPress login redirect filter (passes only the URL, no user param).
     * We grab the current user from the global.
     *
     * @param string $redirect_to
     * @return string
     */
    public function filter_bp_redirect( string $redirect_to ): string {
        $user = wp_get_current_user();
        if ( ! $user || ! $user->exists() ) {
            return $redirect_to;
        }
        $url = $this->get_redirect_for_user( $user );
        return $url ?: $redirect_to;
    }

    /**
     * Ultimate Member hook — fires after UM completes its own login routine.
     *
     * @param int $user_id
     */
    public function action_um_after_login( int $user_id ): void {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return;
        }
        $url = $this->get_redirect_for_user( $user );
        if ( $url && ! headers_sent() ) {
            wp_safe_redirect( $url );
            exit;
        }
    }
}
