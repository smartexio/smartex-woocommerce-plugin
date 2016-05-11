'use strict';

(function ( $ ) {

  $(function () {

    /**
     * Update the API Token helper link on Network selection
    */

    $('#smartex_api_token_form').on('change', '.smartex-pairing__network', function (e) {

      // Helper urls
      var livenet = 'https://smartex.io/api-tokens';
      var testnet = 'https://test.smartex.io/api-tokens';

      if ($('.smartex-pairing__network').val() === 'livenet') {
        $('.smartex-pairing__link').attr('href', livenet).html(livenet);
      } else {
        $('.smartex-pairing__link').attr('href', testnet).html(testnet);
      }

    });

    /**
     * Try to pair with Smartex using an entered pairing code
    */
    $('#smartex_api_token_form').on('click', '.smartex-pairing__find', function (e) {

      // Don't submit any forms or follow any links
      e.preventDefault();

      // Hide the pairing code form
      $('.smartex-pairing').hide();
      $('.smartex-pairing').after('<div class="smartex-pairing__loading" style="width: 20em; text-align: center"><img src="'+ajax_loader_url+'"></div>');

      // Attempt the pair with Smartex
      $.post(SmartexAjax.ajaxurl, {
        'action':       'smartex_pair_code',
        'pairing_code': $('.smartex-pairing__code').val(),
        'network':      $('.smartex-pairing__network').val(),
        'pairNonce':    SmartexAjax.pairNonce
      })
      .done(function (data) {

        $('.smartex-pairing__loading').remove();

        // Make sure the data is valid
        if (data && data.sin && data.label) {

          // Set the token values on the template
          $('.smartex-token').removeClass('smartex-token--livenet').removeClass('smartex-token--testnet').addClass('smartex-token--'+data.network);
          $('.smartex-token__token-label').text(data.label);
          $('.smartex-token__token-sin').text(data.sin);

          // Display the token and success notification
          $('.smartex-token').hide().removeClass('smartex-token--hidden').fadeIn(500);
          $('.smartex-pairing__code').val('');
          $('.smartex-pairing__network').val('livenet');
          $('#message').remove();
          $('h2.woo-nav-tab-wrapper').after('<div id="message" class="updated fade"><p><strong>You have been paired with your Smartex account!</strong></p></div>');
        }
        // Pairing failed
        else if (data && data.success === false) {
          $('.smartex-pairing').show();
          alert('Unable to pair with Smartex.');
        }

      });
    });

    // Revoking Token
    $('#smartex_api_token_form').on('click', '.smartex-token__revoke', function (e) {

      // Don't submit any forms or follow any links
      e.preventDefault();

      if (confirm('Are you sure you want to revoke the token?')) {
        $.post(SmartexAjax.ajaxurl, {
          'action': 'smartex_revoke_token',
          'revokeNonce':    SmartexAjax.revokeNonce
        })
        .always(function (data) {
          $('.smartex-token').fadeOut(500, function () {
            $('.smartex-pairing').removeClass('.smartex-pairing--hidden').show();
            $('#message').remove();
            $('h2.woo-nav-tab-wrapper').after('<div id="message" class="updated fade"><p><strong>You have revoked your token!</strong></p></div>');
          });
        });
      }

    });

  });

}( jQuery ));
