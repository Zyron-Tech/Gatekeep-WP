/* Zyron Login Redirect — Admin JS  v2.0.0
   Author: Zyron Tech · https://zyron-portfolio.vercel.app/ */

( function ( $ ) {
    'use strict';

    var ZLR_CFG = window.ZLR || {};

    /* ================================================================
       Shared helpers
    ================================================================ */

    function getTemplate( id ) {
        var tpl = document.getElementById( id );
        return tpl ? document.importNode( tpl.content, true ) : null;
    }

    function emptyRowHtml( cols, id, label ) {
        return '<tr class="zlr-row zlr-row--empty" id="' + id + '">' +
            '<td colspan="' + cols + '">' +
            '<div class="zlr-empty">' +
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none" width="48" height="48">' +
            '<circle cx="24" cy="24" r="22" stroke="currentColor" stroke-width="2" stroke-dasharray="4 3"/>' +
            '<path d="M24 16v8M24 30v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>' +
            '</svg>' +
            '<p>No rules yet. Click <strong>+ Add ' + label + '</strong> to get started.</p>' +
            '</div></td></tr>';
    }

    function highlightError( $el ) {
        $el.css( 'border-color', '#ef4444' );
        $el.one( 'input change', function () {
            $( this ).css( 'border-color', '' );
        } );
    }

    /* ================================================================
       LOGIN REDIRECT tab
    ================================================================ */

    var $loginBody = $( '#zlr-rules-body' );
    var $addBtn    = $( '#zlr-add-rule' );

    function loginHideEmpty()  { $( '#zlr-empty-row' ).remove(); }
    function loginMaybeEmpty() {
        if ( $loginBody.find( '[data-row]' ).length === 0 ) {
            $loginBody.html( emptyRowHtml( 3, 'zlr-empty-row', 'Rule' ) );
        }
    }

    $addBtn.on( 'click', function () {
        var clone = getTemplate( 'zlr-row-template' );
        if ( ! clone ) return;
        loginHideEmpty();
        var $row = $( clone.querySelector ? clone : clone ).find ? $( clone ) : $( '<tbody>' ).append( clone ).children();
        // Re-grab after append
        $loginBody.append( clone );
        $loginBody.find( '[data-row]:last select' ).trigger( 'focus' );
        $loginBody.find( '[data-row]:last' ).addClass( 'zlr-row--new' );
    } );

    $loginBody.on( 'click', '[data-remove]', function () {
        if ( ! confirm( ZLR_CFG.confirm_delete || 'Remove this redirect rule?' ) ) return;
        $( this ).closest( '[data-row]' ).fadeOut( 180, function () {
            $( this ).remove();
            loginMaybeEmpty();
        } );
    } );

    // Duplicate role guard
    $loginBody.on( 'change', 'select', function () {
        var $changed = $( this );
        var val      = $changed.val();
        if ( ! val ) return;
        var dupes = 0;
        $loginBody.find( 'select' ).each( function () {
            if ( $( this ).val() === val ) dupes++;
        } );
        if ( dupes > 1 ) {
            alert( 'A rule for this role already exists. Please choose a different role or update the existing one.' );
            $changed.val( '' );
        }
    } );

    // Validate before save
    $( '#zlr-form' ).on( 'submit', function ( e ) {
        var valid = true;
        $loginBody.find( '[data-row]' ).each( function () {
            var $r = $( this );
            var $s = $r.find( 'select' );
            var $u = $r.find( 'input[type="url"]' );
            if ( ! $s.val() ) { highlightError( $s ); valid = false; }
            if ( ! $u.val() ) { highlightError( $u ); valid = false; }
        } );
        if ( ! valid ) {
            e.preventDefault();
            alert( 'Please fill in both the role and URL for each redirect rule before saving.' );
        }
    } );

    /* ================================================================
       ACCESS GUARD tab
    ================================================================ */

    var $accessBody   = $( '#zlr-access-body' );
    var $addAccessBtn = $( '#zlr-add-access-rule' );

    function accessHideEmpty()  { $( '#zlr-access-empty' ).remove(); }
    function accessMaybeEmpty() {
        if ( $accessBody.find( '[data-access-row]' ).length === 0 ) {
            $accessBody.html( emptyRowHtml( 5, 'zlr-access-empty', 'Access Rule' ) );
        }
    }

    $addAccessBtn.on( 'click', function () {
        var clone = getTemplate( 'zlr-access-row-template' );
        if ( ! clone ) return;
        accessHideEmpty();
        $accessBody.append( clone );
        $accessBody.find( '[data-access-row]:last select' ).trigger( 'focus' );
        $accessBody.find( '[data-access-row]:last' ).addClass( 'zlr-row--new' );
    } );

    $accessBody.on( 'click', '[data-access-remove]', function () {
        if ( ! confirm( ZLR_CFG.confirm_delete_access || 'Remove this access rule?' ) ) return;
        $( this ).closest( '[data-access-row]' ).fadeOut( 180, function () {
            $( this ).remove();
            accessMaybeEmpty();
        } );
    } );

    // Validate before save
    $( '#zlr-access-form' ).on( 'submit', function ( e ) {
        var valid = true;
        $accessBody.find( '[data-access-row]' ).each( function () {
            var $r      = $( this );
            var $role   = $r.find( 'select' );
            var $source = $r.find( 'input[name="zlr_ag_source[]"]' );
            var $dest   = $r.find( 'input[name="zlr_ag_destination[]"]' );
            if ( ! $role.val() )   { highlightError( $role );   valid = false; }
            if ( ! $source.val() ) { highlightError( $source ); valid = false; }
            if ( ! $dest.val() )   { highlightError( $dest );   valid = false; }
        } );
        if ( ! valid ) {
            e.preventDefault();
            alert( 'Please fill in the role, source URL, and destination URL for each access rule before saving.' );
        }
    } );

    /* ================================================================
       Row drag-to-reorder (Access Guard only — order matters)
    ================================================================ */
    // Only activate if the access body exists
    if ( $accessBody.length ) {
        var $dragging = null;

        $accessBody.on( 'mousedown', '[data-access-row]', function ( e ) {
            // Only drag if not clicking a button or input
            if ( $( e.target ).closest( 'button, input, select' ).length ) return;
            $dragging = $( this );
            $dragging.addClass( 'zlr-row--dragging' );
        } );

        $accessBody.on( 'mouseover', '[data-access-row]', function () {
            if ( ! $dragging || $( this ).is( $dragging ) ) return;
            var $over   = $( this );
            var overIdx = $over.index();
            var dragIdx = $dragging.index();
            if ( dragIdx < overIdx ) {
                $over.after( $dragging );
            } else {
                $over.before( $dragging );
            }
        } );

        $( document ).on( 'mouseup', function () {
            if ( $dragging ) {
                $dragging.removeClass( 'zlr-row--dragging' );
                $dragging = null;
            }
        } );
    }

} )( jQuery );
