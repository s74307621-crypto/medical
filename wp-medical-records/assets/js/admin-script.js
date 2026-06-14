jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    let selectedPatientId = null;
    let currentRecordId = null;
    
    // Initialize Select2 for patient selection
    function initSelect2() {
        $('#wmr-patient-select').select2({
            ajax: {
                url: wmrAdmin.ajaxUrl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        action: 'wmr_get_patients',
                        nonce: wmrAdmin.nonce,
                        term: params.term
                    };
                },
                processResults: function(data) {
                    if (data.success && data.data) {
                        return {
                            results: data.data.map(function(patient) {
                                return {
                                    id: patient.id,
                                    text: patient.full_name + ' (' + patient.phone + ')'
                                };
                            })
                        };
                    }
                    return { results: [] };
                }
            },
            minimumInputLength: 0,
            placeholder: 'Search and select patient...',
            allowClear: true
        });
    }
    
    // Load patients list
    function loadPatientsList() {
        $.ajax({
            url: wmrAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wmr_get_patients',
                nonce: wmrAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '';
                    response.data.forEach(function(patient) {
                        html += '<tr>';
                        html += '<td>' + patient.id + '</td>';
                        html += '<td>' + patient.full_name + '</td>';
                        html += '<td>' + patient.phone + '</td>';
                        html += '<td>';
                        html += '<button class="button button-small wmr-view-record-btn" data-patient-id="' + patient.id + '">' + wmrAdmin.strings.viewRecord + '</button>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    $('#wmr-patients-tbody').html(html);
                } else {
                    $('#wmr-patients-tbody').html('<tr><td colspan="4" class="text-center">' + wmrAdmin.strings.noPatients + '</td></tr>');
                }
            },
            error: function() {
                $('#wmr-patients-tbody').html('<tr><td colspan="4" class="text-center">' + wmrAdmin.strings.error + '</td></tr>');
            }
        });
    }
    
    // Show toast notification
    function showToast(message, type) {
        const toast = $('<div class="wmr-toast ' + type + '">' + message + '</div>');
        $('body').append(toast);
        setTimeout(function() {
            toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Open modal
    function openModal(modalId) {
        $(modalId).fadeIn();
    }
    
    // Close modal
    function closeModal(modalId) {
        $(modalId).fadeOut();
    }
    
    // Load create record form
    function loadCreateRecordForm() {
        $.ajax({
            url: wmrAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wmr_create_record_form',
                nonce: wmrAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wmr-create-record-form-container').html(response.data.html);
                    initSelect2();
                    openModal('#wmr-create-record-modal');
                    
                    // Handle patient selection
                    $('#wmr-patient-select').on('change', function() {
                        const patientId = $(this).val();
                        if (patientId) {
                            const patientData = $(this).select2('data')[0];
                            const patientName = patientData.text.split(' (')[0];
                            const patientPhone = patientData.text.match(/\(([^)]+)\)/)[1];
                            
                            $('#wmr-preview-name').text(patientName);
                            $('#wmr-preview-phone').text(patientPhone);
                            $('#wmr-patient-info-preview').slideDown();
                            selectedPatientId = patientId;
                        } else {
                            $('#wmr-patient-info-preview').slideUp();
                            selectedPatientId = null;
                        }
                    });
                    
                    // Handle back button
                    $('#wmr-back-select-btn').on('click', function() {
                        $('#wmr-patient-info-preview').slideUp();
                        $('#wmr-patient-select').val(null).trigger('change');
                        selectedPatientId = null;
                    });
                    
                    // Handle confirm create
                    $('#wmr-confirm-create-btn').on('click', function() {
                        if (!selectedPatientId) {
                            showToast('Please select a patient', 'error');
                            return;
                        }
                        
                        createMedicalRecord(selectedPatientId);
                    });
                }
            }
        });
    }
    
    // Create medical record
    function createMedicalRecord(patientId) {
        $.ajax({
            url: wmrAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wmr_create_record',
                nonce: wmrAdmin.nonce,
                patient_id: patientId
            },
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');
                    closeModal('#wmr-create-record-modal');
                    loadPatientsList();
                } else {
                    showToast(response.data.message || wmrAdmin.strings.error, 'error');
                }
            },
            error: function() {
                showToast(wmrAdmin.strings.error, 'error');
            }
        });
    }
    
    // View medical record
    function viewRecord(recordId) {
        $.ajax({
            url: wmrAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wmr_view_record',
                nonce: wmrAdmin.nonce,
                record_id: recordId
            },
            success: function(response) {
                if (response.success) {
                    $('#wmr-patients-list').hide();
                    $('#wmr-view-record-container').html(response.data.html).show();
                    currentRecordId = recordId;
                    
                    // Initialize date pickers
                    $('.wmr-date-picker').persianDatepicker({
                        format: 'YYYY-MM-DD',
                        initialValue: false
                    });
                    
                    // Load visits
                    loadVisits(recordId);
                    
                    // Load clinics filter
                    loadClinicsFilter();
                    
                    // Back to list
                    $('#wmr-back-to-list').on('click', function() {
                        $('#wmr-view-record-container').hide();
                        $('#wmr-patients-list').show();
                    });
                    
                    // Apply filters
                    $('#wmr-apply-filters').on('click', function() {
                        const clinicId = $('#wmr-filter-clinic').val();
                        const doctorId = $('#wmr-filter-doctor').val();
                        const dateFrom = $('#wmr-filter-date-from').val();
                        const dateTo = $('#wmr-filter-date-to').val();
                        
                        loadVisits(recordId, clinicId, doctorId, dateFrom, dateTo);
                    });
                }
            }
        });
    }
    
    // Load visits
    function loadVisits(recordId, clinicId, doctorId, dateFrom, dateTo) {
        $.ajax({
            url: wmrAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wmr_get_visits',
                nonce: wmrAdmin.nonce,
                record_id: recordId,
                clinic_id: clinicId || 0,
                doctor_id: doctorId || 0,
                date_from: dateFrom || '',
                date_to: dateTo || ''
            },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '';
                    response.data.forEach(function(visit) {
                        html += '<tr>';
                        html += '<td>' + visit.visit_date + '</td>';
                        html += '<td>' + visit.doctor_name + '</td>';
                        html += '<td>' + visit.clinic_name + '</td>';
                        html += '<td><button class="button button-small wmr-view-visit-btn" data-visit-id="' + visit.id + '">' + wmrAdmin.strings.viewFull + '</button></td>';
                        html += '</tr>';
                    });
                    $('#wmr-visits-tbody').html(html);
                } else {
                    $('#wmr-visits-tbody').html('<tr><td colspan="4" class="text-center">No visits found</td></tr>');
                }
            }
        });
    }
    
    // Load clinics filter
    function loadClinicsFilter() {
        $.ajax({
            url: wmrAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wmr_get_clinics',
                nonce: wmrAdmin.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '<option value="0">All Clinics</option>';
                    response.data.forEach(function(clinic) {
                        html += '<option value="' + clinic.id + '">' + clinic.name + '</option>';
                    });
                    $('#wmr-filter-clinic').html(html);
                }
            }
        });
    }
    
    // View visit details
    function viewVisit(visitId) {
        $.ajax({
            url: wmrAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wmr_view_visit',
                nonce: wmrAdmin.nonce,
                visit_id: visitId
            },
            success: function(response) {
                if (response.success) {
                    $('#wmr-view-visit-container').html(response.data.html);
                    openModal('#wmr-view-visit-modal');
                    
                    // Handle file lightbox
                    $('.wmr-file-link').on('click', function(e) {
                        e.preventDefault();
                        const fileUrl = $(this).data('file-url');
                        const fileName = $(this).data('file-name');
                        
                        let content = '';
                        if (fileUrl.match(/\.(jpg|jpeg|png|gif)$/i)) {
                            content = '<img src="' + fileUrl + '" alt="' + fileName + '" style="max-width: 100%;">';
                        } else {
                            content = '<a href="' + fileUrl + '" target="_blank" class="button button-primary">Download ' + fileName + '</a>';
                        }
                        
                        $('#wmr-lightbox-body').html(content);
                        $('#wmr-file-lightbox').fadeIn();
                    });
                    
                    $('#wmr-file-lightbox .wmr-lightbox-close, #wmr-view-visit-modal .wmr-close').on('click', function() {
                        $('#wmr-file-lightbox, #wmr-view-visit-modal').fadeOut();
                    });
                }
            }
        });
    }
    
    // Event delegation for dynamically created elements
    $(document).on('click', '.wmr-view-record-btn', function() {
        const patientId = $(this).data('patient-id');
        // You would need to get the record ID from the backend
        // For now, this is a placeholder
        alert('View record functionality - implement record lookup');
    });
    
    $(document).on('click', '.wmr-view-visit-btn', function() {
        const visitId = $(this).data('visit-id');
        viewVisit(visitId);
    });
    
    // Initialize on page load
    loadPatientsList();
    
    // Modal close handlers
    $('.wmr-close').on('click', function() {
        $(this).closest('.wmr-modal').fadeOut();
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('wmr-modal')) {
            $(e.target).fadeOut();
        }
    });
});
