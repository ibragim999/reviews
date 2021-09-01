
var ReviewsObject = {
    init: function(ReviewsParams){
        $("body").on('click', ".reviews-button-next", function () {
            var data = Object.assign({}, ReviewsParams.data);
            if(!ReviewsObject.page) ReviewsObject.page = 0;
            data.page = ++ReviewsObject.page;
            var query = {
                c: 'opensource:reviews',
                action: 'nextReviews',
                mode: 'ajax',
            };

            var $this = $(this);
            $.ajax({
                url: '/bitrix/services/main/ajax.php?' + $.param(query, true),
                data: data,
                type: 'post',
                beforeSend: function () {
                    $this.find('.spinner-border').removeClass('d-none');
                },
                complete: function () {
                    $this.find('.spinner-border').addClass('d-none');
                },
                success: function (response) {
                    if(response.data && response.data.html) {
                        var html = response.data.html;
                        if (!html || $('<div>' + html + '</div>').find('.last-items').length > 0) {
                            $this.closest('.reviews-button').hide();
                        }
                        $("#" + ReviewsParams.reviews_id).append($(html).find(".reviews-items > div").html());
                    }
                }
            });
        }).on('submit', "#" + ReviewsParams.reviews_id + '_form', function (e) {
            e.preventDefault();
            var $this = $(this);
            var data = Object.assign({}, ReviewsParams.data);
            var formValues = $this.serializeArray();
            for (var i in formValues){
                if(formValues.hasOwnProperty(i)){
                    data[formValues[i].name] = formValues[i].value;
                }
            }

            var query = {
                c: 'opensource:reviews',
                action: 'addReview',
                mode: 'ajax',
            };

            $.ajax({
                url: '/bitrix/services/main/ajax.php?' + $.param(query, true),
                data: {values: data},
                type: 'post',
                beforeSend: function () {
                    $this.find('.alert, .invalid-feedback').remove();
                    $this.find('input, select, textarea, button').prop('disabled', true);
                    $this.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid')
                    $this.find('.btn .spinner-border').show();
                },
                complete: function () {
                    $this.find('textarea, input, button, select').prop('disabled', false);
                    $this.find('.spinner-border').addClass('d-none');
                },
                success: function (response) {
                    if(response.status === 'error'){
                        for (var i in response.errors){
                            if(response.errors.hasOwnProperty(i)){
                                var error = response.errors[i];
                                if(!/^[0-9]+$/.test(error.code)){
                                    var $input = $this.find('[name="'+error.code+'"]:last');
                                    $input.addClass('is-invalid');
                                    var err = '<div class="invalid-feedback text-left">' + error.message + '</div>';
                                    if($input.is('[type="radio"]')){
                                        $input.nextAll().last().after(err);
                                    } else {
                                        $input.after(err);
                                    }
                                } else {
                                    $this.prepend('<div class="alert text-left alert-danger">' + error.message + '</div>');
                                }
                            }
                        }
                    } else {
                        if (response.data && response.data.html) {
                            $('.reviews_w').html($('<div>' + response.data.html + '</div>').find(".reviews_w").html());
                        }

                        if(response.data && response.data.message){
                            $this.hide();
                            $this.after('<div class="alert text-left alert-success">' + response.data.message + '</div>');
                        } else {
                            $('.modal').modal('hide');
                        }
                        $this.get(0).reset();
                    }
                }
            });
            return false;
        });
    }
}