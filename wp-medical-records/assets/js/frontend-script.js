jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    let currentRecordId = null;
    
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
    
    // Load patient visits
    function loadPatientVisits() {
        const recordId = $('#wmr-patient-record-id').val();
        
        $.ajax({
            url: wmrFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wmr_get_patient_visits',
                nonce: wmrFrontend.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    let html = '';
                    response.data.forEach(function(visit) {
                        html += '<tr>';
                        html += '<td>' + visit.visit_date + '</td>';
                        html += '<td>' + visit.doctor_name + '</td>';
                        html += '<td>' + visit.clinic_name + '</td>';
                        html += '<td><button class="button button-small wmr-patient-view-visit-btn" data-visit-id="' + visit.id + '">' + wmrFrontend.strings.viewFull + '</button></td>';
                        html += '</tr>';
                    });
                    $('#wmr-patient-visits-tbody').html(html);
                } else {
                    $('#wmr-patient-visits-tbody').html('<tr><td colspan="4" class="text-center">No visits found</td></tr>');
                }
            },
            error: function() {
                $('#wmr-patient-visits-tbody').html('<tr><td colspan="4" class="text-center">' + wmrFrontend.strings.error + '</td></tr>');
            }
        });
    }
    
    // View visit details
    function viewPatientVisit(visitId) {
        $.ajax({
            url: wmrFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wmr_view_patient_visit',
                nonce: wmrFrontend.nonce,
                visit_id: visitId
            },
            success: function(response) {
                if (response.success) {
                    $('#wmr-patient-view-visit-content').html(response.data.html);
                    openModal('#wmr-patient-view-visit-modal');
                    
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
                        
                        $('#wmr-patient-lightbox-body').html(content);
                        $('#wmr-patient-file-lightbox').fadeIn();
                    });
                    
                    $('#wmr-patient-file-lightbox .wmr-lightbox-close, #wmr-patient-view-visit-modal .wmr-close').on('click', function() {
                        $('#wmr-patient-file-lightbox, #wmr-patient-view-visit-modal').fadeOut();
                    });
                }
            }
        });
    }
    
    // Medical history form
    function showMedicalHistoryForm() {
        const recordId = $('#wmr-patient-record-id').val();
        
        const diseasesList = [
            'دیابت (Diabetes)',
            'فشار خون بالا (Hypertension)',
            'بیماری قلبی (Heart Disease)',
            'آسم (Asthma)',
            'آلرژی (Allergy)',
            'تیروئید (Thyroid)',
            'پوکی استخوان (Osteoporosis)',
            'افسردگی (Depression)',
            'اضطراب (Anxiety)',
            'سایر (Other)'
        ];
        
        let diseasesHtml = '';
        diseasesList.forEach(function(disease) {
            diseasesHtml += '<label style="display: inline-block; margin: 5px 10px 5px 0;">';
            diseasesHtml += '<input type="checkbox" name="diseases[]" value="' + disease + '"> ' + disease;
            diseasesHtml += '</label>';
        });
        
        const formHtml = `
            <h3>${wmrFrontend.strings.medicalHistory}</h3>
            <div class="wmr-form-group">
                <label>${wmrFrontend.strings.bloodType}</label>
                <select name="blood_type">
                    <option value="">${wmrFrontend.strings.selectBloodType}</option>
                    <option value="A+">A+</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B-">B-</option>
                    <option value="AB+">AB+</option>
                    <option value="AB-">AB-</option>
                    <option value="O+">O+</option>
                    <option value="O-">O-</option>
                </select>
            </div>
            <div class="wmr-form-group">
                <label>${wmrFrontend.strings.age}</label>
                <input type="number" name="age" min="1" max="120">
            </div>
            <div class="wmr-form-group">
                <label>${wmrFrontend.strings.diseases}</label>
                <div>${diseasesHtml}</div>
            </div>
            <div class="wmr-form-group">
                <label>${wmrFrontend.strings.currentMedications}</label>
                <div id="wmr-medications-container"></div>
                <button type="button" class="button wmr-add-medication-btn">${wmrFrontend.strings.addMedication}</button>
            </div>
            <div class="wmr-form-group">
                <label>${wmrFrontend.strings.medicalFiles}</label>
                <div id="wmr-history-files-container"></div>
                <input type="file" id="wmr-history-file-upload" style="margin-top: 10px;">
            </div>
            <div class="wmr-form-actions">
                <button type="button" class="button button-primary wmr-save-history-btn">${wmrFrontend.strings.saveHistory}</button>
                <button type="button" class="button wmr-cancel-history-btn">${wmrFrontend.strings.cancel}</button>
            </div>
        `;
        
        $('#wmr-patient-history-form').html(formHtml);
        openModal('#wmr-patient-history-modal');
        
        // Add medication field
        $('.wmr-add-medication-btn').on('click', function() {
            addMedicationField();
        });
        
        // File upload
        $('#wmr-history-file-upload').on('change', function() {
            uploadHistoryFile($(this));
        });
        
        // Save history
        $('.wmr-save-history-btn').on('click', function() {
            saveMedicalHistory(recordId);
        });
        
        // Cancel
        $('.wmr-cancel-history-btn').on('click', function() {
            closeModal('#wmr-patient-history-modal');
        });
    }
    
    // Add medication field
    function addMedicationField() {
        const index = $('#wmr-medications-container .wmr-repeating-field').length;
        const fieldHtml = `
            <div class="wmr-repeating-field">
                <span class="wmr-repeating-field-remove" onclick="$(this).parent().remove()">×</span>
                <input type="text" name="current_medications[${index}][name]" placeholder="${wmrFrontend.strings.medicineName}" style="width: 48%; display: inline-block;">
                <input type="text" name="current_medications[${index}][dosage]" placeholder="${wmrFrontend.strings.dosage}" style="width: 48%; display: inline-block;">
            </div>
        `;
        $('#wmr-medications-container').append(fieldHtml);
    }
    
    // Upload history file
    function uploadHistoryFile(input) {
        if (input[0].files.length === 0) return;
        
        const formData = new FormData();
        formData.append('action', 'wmr_upload_patient_file');
        formData.append('nonce', wmrFrontend.nonce);
        formData.append('file', input[0].files[0]);
        
        $.ajax({
            url: wmrFrontend.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    const fileHtml = `
                        <div class="wmr-repeating-field">
                            <span class="wmr-repeating-field-remove" onclick="$(this).parent().remove()">×</span>
                            <input type="hidden" name="files[]" value='${JSON.stringify(response.data)}'>
                            <span>${response.data.name}</span>
                        </div>
                    `;
                    $('#wmr-history-files-container').append(fileHtml);
                    input.val('');
                    showToast(wmrFrontend.strings.fileUploaded, 'success');
                } else {
                    showToast(response.data.message || wmrFrontend.strings.error, 'error');
                }
            },
            error: function() {
                showToast(wmrFrontend.strings.error, 'error');
            }
        });
    }
    
    // Save medical history
    function saveMedicalHistory(recordId) {
        const formData = {
            action: 'wmr_save_patient_medical_history',
            nonce: wmrFrontend.nonce,
            record_id: recordId,
            blood_type: $('[name="blood_type"]').val(),
            age: $('[name="age"]').val(),
            diseases: [],
            current_medications: [],
            files: []
        };
        
        // Get selected diseases
        $('[name="diseases[]"]:checked').each(function() {
            formData.diseases.push($(this).val());
        });
        
        // Get medications
        $('[name^="current_medications["]').each(function() {
            const match = $(this).attr('name').match(/current_medications\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const index = match[1];
                const field = match[2];
                if (!formData.current_medications[index]) {
                    formData.current_medications[index] = {};
                }
                formData.current_medications[index][field] = $(this).val();
            }
        });
        
        // Get files
        $('[name="files[]"]').each(function() {
            formData.files.push(JSON.parse($(this).val()));
        });
        
        $.ajax({
            url: wmrFrontend.ajaxUrl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');
                    closeModal('#wmr-patient-history-modal');
                    // Reload or update the medical history section
                } else {
                    showToast(response.data.message || wmrFrontend.strings.error, 'error');
                }
            },
            error: function() {
                showToast(wmrFrontend.strings.error, 'error');
            }
        });
    }
    
    // Event delegation
    $(document).on('click', '.wmr-patient-view-visit-btn', function() {
        const visitId = $(this).data('visit-id');
        viewPatientVisit(visitId);
    });
    
    $(document).on('click', '#wmr-patient-save-history-btn', function() {
        showMedicalHistoryForm();
    });
    
    // Modal close handlers
    $('.wmr-close').on('click', function() {
        $(this).closest('.wmr-modal').fadeOut();
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('wmr-modal')) {
            $(e.target).fadeOut();
        }
    });
    
    // Initialize
    loadPatientVisits();
});
