    // -------   Mail Send ajax

     (document).ready(function() {
    var form = $('#myForm');
    var submit = $('.submit-btn');
    var alert = $('.alert-msg');

    form.on('submit', function(e) {
        e.preventDefault();
        
        submit.html('Invio in corso....');
        alert.fadeOut();
        
        // Submit to Formspree
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(data) {
                alert.html('<div class="alert alert-success">Grazie! La tua RSVP è stata ricevuta con successo.</div>').fadeIn();
                form.trigger('reset');
                submit.html('R.S.V.P');
            },
            error: function(e) {
                alert.html('<div class="alert alert-danger">Errore. Per favore riprova.</div>').fadeIn();
                submit.html('R.S.V.P');
            }
        });
    });
});