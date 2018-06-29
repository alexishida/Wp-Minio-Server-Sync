
const ms3_loader = jQuery('.ms3__loader')
const ms3_message = jQuery('.ms3__message')
const ms3_test_connection = jQuery('.ms3__test__connection')

jQuery( function () {

  // check connection button
  ms3_test_connection.on( 'click', function () {

    console.log( 'Testing connection to Minio Server Container' )

    const data = {
      ms3_key: jQuery('input[name=ms3_key]').val(),
      ms3_secret: jQuery('input[name=ms3_secret]').val(),
      ms3_endpoint: jQuery('input[name=ms3_endpoint]').val(),
      ms3_container: jQuery('input[name=ms3_container]').val(),
      action: 'ms3_test_connection'
    }

    ms3_loader.hide()

    jQuery.ajax({
      type: 'POST',
      url: ajaxurl,
      data: data,
      dataType: 'html'
    }).done( function ( res ) {
      ms3_message.show()
      ms3_message.html('<br/>' + res)
      ms3_loader.hide()
      jQuery('html,body').animate({ scrollTop: 0 }, 1000)
    })

  })

})