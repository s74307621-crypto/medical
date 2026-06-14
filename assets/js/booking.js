(function($){
    $(document).ready(function(){
        // Initialize Persian datepicker on inputs with class .mr-date
        if ($.fn.persianDatepicker) {
            $('.mr-date').each(function(){
                $(this).persianDatepicker({
                    format: 'YYYY/MM/DD',
                    initialValue: false,
                    toolbox: {
                        calendarSwitch: {
                            enabled: false
                        }
                    }
                }).on('change', function(){
                    // when date changes, try to fetch times
                    var doctorId = $('input[name="doctor_id"]').val() || $('input[name="doctor_id"]').data('value');
                    var date = $(this).val();
                    if (doctorId && date) {
                        mr_fetch_available_times(doctorId, date);
                    }
                });
            });
        }

        // Click handlers for appointment type boxes and doctor options
        $(document).on('click', '.mr-doctor-option', function(){
            $('.mr-doctor-option').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            var id = $(this).find('input[type="radio"]').val();
            mr_get_doctor_services(id);
        });

        $(document).on('click', '.mr-appointment-type-box', function(){
            $('.mr-appointment-type-box').removeClass('selected');
            $(this).addClass('selected');
            $(this).find('input[type="radio"]').prop('checked', true);
            mr_update_price();
        });

        $(document).on('change', '.duration-select', function(){
            mr_update_online_price();
        });

        // Expose functions globally for compatibility
        window.mr_get_doctor_services = mr_get_doctor_services;
        window.mr_toggle_online_types = mr_toggle_online_types;
        window.mr_select_appointment_type = mr_select_appointment_type;
        window.mr_select_online_type = mr_select_online_type;

        // initial price update
        mr_update_price();
    });

    function mr_get_doctor_services(doctorId) {
        if (!doctorId) return;
        $.post(mr_ajax.ajax_url, {
            action: 'mr_get_doctor_services',
            doctor_id: doctorId,
            _wpnonce: mr_ajax.booking_nonce
        }, function(response){
            if (response.success) {
                var services = response.data;
                $('#appointment-types-container').show();

                // in person
                if (services.in_person && services.in_person.enabled) {
                    $('#type_in_person').closest('div').show();
                    $('#price_in_person').text(services.in_person.price || 0);
                } else {
                    $('#type_in_person').closest('div').hide();
                }

                // online
                if (services.online && services.online.enabled) {
                    $('#type_online').closest('div').show();
                    var onlineTypes = services.online.types || {};

                    if (onlineTypes.video && onlineTypes.video.enabled) {
                        $("select[name=\"video_duration\"]").closest('div').show().find('select').data('prices', onlineTypes.video.prices || {});
                    }
                    if (onlineTypes.audio && onlineTypes.audio.enabled) {
                        $("select[name=\"audio_duration\"]").closest('div').show().find('select').data('prices', onlineTypes.audio.prices || {});
                    }
                    if (onlineTypes.text && onlineTypes.text.enabled) {
                        $("select[name=\"text_duration\"]").closest('div').show().find('select').data('prices', onlineTypes.text.prices || {});
                    }
                    if (onlineTypes.phone && onlineTypes.phone.enabled) {
                        $("select[name=\"phone_duration\"]").closest('div').show().find('select').data('prices', onlineTypes.phone.prices || {});
                    }
                } else {
                    $('#type_online').closest('div').hide();
                }
            }
        });
    }

    function mr_toggle_online_types() {
        $('#online-types-container').toggle();
    }

    function mr_select_appointment_type(type) {
        $('input[name="appointment_type"]').prop('checked', false);
        if (type === 'in_person') {
            $('#type_in_person').prop('checked', true);
        } else if (type === 'online') {
            $('#type_online').prop('checked', true);
        }
        mr_update_price();
    }

    function mr_select_online_type(type, element) {
        $('.online-type-radio').prop('checked', false);
        $(element).find('.online-type-radio').prop('checked', true);
        $('#type_online').prop('checked', true);
        $('.duration-select').hide();
        $('select[name="' + type + '_duration"]').show();
        mr_update_online_price();
    }

    function mr_update_price() {
        var appointmentType = $('input[name="appointment_type"]:checked').val();
        var price = 0;
        if (appointmentType === 'in_person') {
            price = parseInt($('#price_in_person').text()) || 0;
            $('#price-summary').show();
        } else if (appointmentType === 'online') {
            var onlineType = $('.online-type-radio:checked').val();
            if (onlineType) {
                mr_update_online_price();
                return;
            }
        } else {
            $('#price-summary').hide();
        }
        $('#final-price').text(price);
    }

    function mr_update_online_price() {
        var duration = $('.duration-select:visible').val() || 0;
        var type = $('.online-type-radio:checked').val();
        if (!duration || !type) {
            $('#price-summary').hide();
            return;
        }
        var prices = $('.duration-select:visible').data('prices') || {};
        var price = prices[duration] || 0;
        $('#final-price').text(price);
        $('#price-summary').show();
    }

    function mr_fetch_available_times(doctorId, date) {
        $.post(mr_ajax.ajax_url, {
            action: 'mr_get_available_times',
            doctor_id: doctorId,
            appointment_date: date,
            _wpnonce: mr_ajax.booking_nonce
        }, function(response){
            if (response.success) {
                var times = response.data || [];
                var $select = $('#appointment_time');
                $select.empty();
                if (!times.length) {
                    $select.append('<option value="">هیچ ساعتی موجود نیست</option>');
                } else {
                    $select.append('<option value="">-- لطفاً ساعت را انتخاب کنید --</option>');
                    times.forEach(function(t){
                        $select.append('<option value="'+t+'">'+t+'</option>');
                    });
                }
            }
        });
    }

})(jQuery);