    // -------   Mail Send ajax

     $(document).ready(function() {
        var form = $('#myForm'); // RSVP form
        var submit = $('.submit-btn'); // submit button
        var alert = $('.alert-msg'); // alert div for show alert message

        // form submit event
        form.on('submit', function(e) {
            e.preventDefault(); // prevent default form submit

            $.ajax({
                url: 'mail.php', // form action url
                type: 'POST', // form submit method get/post
                dataType: 'json', // request type html/json/xml
                data: form.serialize(), // serialize form data
                beforeSend: function() {
                    alert.fadeOut();
                    submit.html('Invio in corso....'); // change submit button text
                },
                success: function(data) {
                    if (data.success) {
                        alert.html('<div class="alert alert-success">' + data.message + '</div>').fadeIn();
                        form.trigger('reset'); // reset form
                    } else {
                        alert.html('<div class="alert alert-danger">' + data.message + '</div>').fadeIn();
                    }
                    submit.html('R.S.V.P'); // reset submit button text
                },
                error: function(e) {
                    console.log('AJAX Error:', e);
                    alert.html('<div class="alert alert-danger">Errore di connessione. Per favore controlla la tua connessione e riprova.</div>').fadeIn();
                    submit.html('R.S.V.P'); // reset submit button text
                }
            });
        });
    });