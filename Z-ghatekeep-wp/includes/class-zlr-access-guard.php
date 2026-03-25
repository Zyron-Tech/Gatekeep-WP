<?php
defined( 'ABSPATH' ) || exit;

/**
 * ZLR_Access_Guard
 *
 * When a logged-in user visits a URL that matches a configured "source" pattern,
 * and their role matches the rule, they are immediately redirected to the
 * configured "destination" URL.
 *
 * Matching supports:
 *  - Exact URL  : https://example.com/members-only/
 *  - Path match : /members-only/
 *  - Wildcard   : /shop/*, /account/*  (trailing * matches anything after)
 *  - Applies to logged-out users too if role is set to "logged_out"
 */
class ZLR_Access_Guard {

    /** @var array  Raw rules from the DB */
    private array $rules;

    public function __construct() {
        $this->rules = (array) get_option( ZLR_ACCESS_KEY, [] );

        if ( empty( $this->rules ) ) {
            return;
        }

        // Fire on template_redirect — after WP has parsed the request but before
        // any output is sent.  Priority 1 so we're early.
        add_action( 'template_redirect', [ $this, 'maybe_redirect' ], 1 );
    }

    /* ------------------------------------------------------------------ */
    /*  Core redirect logic                                                 */
    /* ------------------------------------------------------------------ */

    public function maybe_redirect(): void {
        // Never redirect admin-side requests.
        if ( is_admin() ) {
            return;
        }
        // Never redirect AJAX / REST / Cron.
        if (
            ( defined( 'DOING_AJAX' )    && DOING_AJAX )    ||
            ( defined( 'REST_REQUEST' )  && REST_REQUEST )  ||
            ( defined( 'DOING_CRON' )    && DOING_CRON )
        ) {
            return;
        }

        $current_url  = $this->get_current_url();
        $current_path = $this->get_current_path();

        $user         = wp_get_current_user();
        $user_roles   = ( $user && $user->exists() ) ? (array) $user->roles : [];
        $is_logged_in = ! empty( $user_roles );

        foreach ( $this->rules as $rule ) {
            if ( empty( $rule['source'] ) || empty( $rule['destination'] ) || empty( $rule['role'] ) ) {
                continue;
            }

            $role = sanitize_key( $rule['role'] );

            // ── Role matching ──
            $role_matches = false;
            if ( 'logged_out' === $role ) {
                $role_matches = ! $is_logged_in;
            } elseif ( 'any' === $role ) {
                $role_matches = true;
            } elseif ( $is_logged_in && in_array( $role, $user_roles, true ) ) {
                $role_matches = true;
            }

            if ( ! $role_matches ) {
                continue;
            }

            // ── URL matching ──
            $source = trim( $rule['source'] );
            if ( $this->url_matches( $source, $current_url, $current_path ) ) {
                $destination = esc_url_raw( $rule['destination'] );

                // Prevent redirect loops
                if ( $this->is_same_url( $destination, $current_url ) ) {
                    continue;
                }

                wp_safe_redirect( $destination, 302 );
                exit;
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Matching helpers                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Test whether a source pattern matches the current URL or path.
     *
     * @param string $source       The pattern stored in the rule.
     * @param string $current_url  Full current URL.
     * @param string $current_path URL path only (e.g. /shop/product/).
     * @return bool
     */
    private function url_matches( string $source, string $current_url, string $current_path ): bool {
        // Normalize trailing slash for comparison
        $source_norm = rtrim( $source, '/' );

        // Wildcard: /shop/* or https://example.com/shop/*
        if ( substr( $source, -1 ) === '*' ) {
            $prefix = rtrim( substr( $source, 0, -1 ), '/' );
            // Full URL wildcard
            if ( filter_var( $prefix, FILTER_VALIDATE_URL ) ) {
                return strpos( rtrim( $current_url, '/' ), $prefix ) === 0;
            }
            // Path wildcard
            return strpos( rtrim( $current_path, '/' ), $prefix ) === 0;
        }

        // Full URL exact match
        if ( filter_var( $source, FILTER_VALIDATE_URL ) ) {
            return rtrim( $current_url, '/' ) === $source_norm;
        }

        // Path-only match  (e.g. /members-only/)
        if ( strpos( $source, '/' ) === 0 ) {
            return rtrim( $current_path, '/' ) === $source_norm;
        }

        // Fallback: partial string match on the full URL
        return strpos( $current_url, $source ) !== false;
    }

    /** Return full current URL including scheme, host, path and query string. */
    private function get_current_url(): string {
        $scheme = is_ssl() ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /** Return just the path component of the current URL. */
    private function get_current_path(): string {
        return parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ?? '/';
    }

    /** Loose comparison to detect redirect loops. */
    private function is_same_url( string $a, string $b ): bool {
        return rtrim( $a, '/' ) === rtrim( $b, '/' );
    }
}
