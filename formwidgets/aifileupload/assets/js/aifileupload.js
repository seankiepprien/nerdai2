+function($) {
    'use strict';

    var Base = $.oc.foundation.base,
        BaseProto = Base.prototype;

    var FileUpload = function(element, options) {
        this.$el = $(element);
        this.options = options || {};

        $.oc.foundation.controlUtils.markDisposable(element);
        Base.call(this);

        this.init();
    }

    FileUpload.prototype = Object.create(BaseProto);
    FileUpload.prototype.constructor = FileUpload;

    FileUpload.DEFAULTS = {
        useNative: false
    }

    FileUpload.prototype.init = function() {
        console.log('Initializing FileUpload widget');

        this.$dropZone = this.$el.find('.nerdai-fileupload-dropzone');
        this.$fileInput = this.$el.find('input[type=file]');
        this.$preview = this.$el.find('.nerdai-fileupload-preview');
        this.$previewImg = this.$preview.find('img');
        this.$analysis = this.$preview.find('.analysis-content');
        this.$error = this.$el.find('.nerdai-fileupload-error');
        this.$errorMessage = this.$error.find('.error-message');

        this.bindEvents();
    }

    FileUpload.prototype.bindEvents = function() {
        this.$dropZone
            .on('dragover.nerdai.fileupload', this.proxy(this.onDragOver))
            .on('dragleave.nerdai.fileupload', this.proxy(this.onDragLeave))
            .on('drop.nerdai.fileupload', this.proxy(this.onDrop))
            .on('click.nerdai.fileupload', this.proxy(this.onClick));

        this.$fileInput.on('change.nerdai.fileupload', this.proxy(this.onFileSelected));
    }

    FileUpload.prototype.onClick = function(e) {
        console.log('Click event triggered');
        // Only trigger file input click if the click wasn't on the input itself
        if (e.target !== this.$fileInput[0]) {
            e.preventDefault();
            e.stopPropagation();
            this.$fileInput.trigger('click');
        }
    }

    FileUpload.prototype.onFileSelected = function(e) {
        console.log('File selected event triggered');
        var files = e.target.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }

    FileUpload.prototype.onDragOver = function(e) {
        e.preventDefault();
        this.$dropZone.addClass('drag-over');
    }

    FileUpload.prototype.onDragLeave = function(e) {
        e.preventDefault();
        this.$dropZone.removeClass('drag-over');
    }

    FileUpload.prototype.onDrop = function(e) {
        e.preventDefault();
        this.$dropZone.removeClass('drag-over');

        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            this.processFile(files[0]);
        }
    }

    FileUpload.prototype.processFile = function(file) {
        console.log('Processing file:', file.name, file.type, file.size);

        if (!file.type.match(/^image\//)) {
            this.showError('Please upload an image file.');
            return;
        }

        // Show preview
        var reader = new FileReader();
        reader.onload = this.proxy(function(e) {
            this.$previewImg.attr('src', e.target.result);
            this.$preview.removeClass('hidden');
            this.$error.addClass('hidden');
        });
        reader.readAsDataURL(file);

        // Create FormData
        var formData = new FormData();
        formData.append('file', file);

        // Get request handler from data attribute
        var handler = this.$dropZone.data('request');
        console.log('Request handler:', handler);

        // Log FormData contents
        for (var pair of formData.entries()) {
            console.log('FormData entry:', pair[0], pair[1]);
        }

        // Create form data and append file
        var formData = new FormData();
        formData.append('file', file);

        // Add CSRF token and session key
        var token = $('meta[name="csrf-token"]').attr('content');
        var sessionKey = $('meta[name="session-key"]').attr('content');

        // Add required OctoberCMS fields
        formData.append('_session_key', sessionKey);
        formData.append('_token', token);

        // Get handler name
        var handler = this.$dropZone.data('request');

        // Show loading indicator
        $.oc.stripeLoadIndicator.show();

        // Make request
        $.ajax({
            url: window.location.pathname,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-OCTOBER-REQUEST-HANDLER': handler,
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: this.proxy(this.onUploadSuccess),
            error: this.proxy(this.onUploadError),
            complete: function() {
                $.oc.stripeLoadIndicator.hide();
            }
        });
    }

    FileUpload.prototype.onUploadSuccess = function(response) {
        console.log('Upload success response:', response);
        try {
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }

            if (response.success) {
                this.$analysis.html(response.analysis);
                this.$preview.removeClass('hidden');
                this.$error.addClass('hidden');
            } else {
                this.showError(response.error || 'Upload failed');
            }
        } catch (e) {
            console.error('Error processing response:', e);
            this.showError('Error processing server response');
        }
    }

    FileUpload.prototype.onUploadError = function(jqXHR, textStatus, errorThrown) {
        console.log('Upload error:', {
            status: textStatus,
            error: errorThrown,
            response: jqXHR.responseText
        });

        try {
            var response = JSON.parse(jqXHR.responseText);
            this.showError(response.error || 'Upload failed. Please try again.');
        } catch (e) {
            this.showError('Upload failed. Please try again.');
        }
    }

    FileUpload.prototype.showError = function(message) {
        console.log('Showing error:', message);
        this.$errorMessage.text(message);
        this.$error.removeClass('hidden');
        this.$preview.addClass('hidden');
    }

    FileUpload.prototype.dispose = function() {
        this.$dropZone.off('.nerdai.fileupload');
        this.$fileInput.off('.nerdai.fileupload');
        this.$el.removeData('oc.nerdaiFileUpload');

        this.$el = null;
        this.$dropZone = null;
        this.$fileInput = null;
        this.$preview = null;
        this.$previewImg = null;
        this.$analysis = null;
        this.$error = null;
        this.$errorMessage = null;

        BaseProto.dispose.call(this);
    }

    FileUpload.prototype.proxy = function(callback) {
        return $.proxy(callback, this);
    }

    var old = $.fn.nerdAiFileUpload;

    $.fn.nerdAiFileUpload = function(option) {
        var args = Array.prototype.slice.call(arguments, 1),
            result;

        this.each(function() {
            var $this = $(this);
            var data = $this.data('oc.nerdaiFileUpload');
            var options = $.extend({}, FileUpload.DEFAULTS, $this.data(), typeof option == 'object' && option);
            if (!data) $this.data('oc.nerdaiFileUpload', (data = new FileUpload(this, options)));
            if (typeof option == 'string') result = data[option].apply(data, args);
            if (typeof result != 'undefined') return false;
        });

        return result ? result : this;
    }

    $.fn.nerdAiFileUpload.Constructor = FileUpload;

    $.fn.nerdAiFileUpload.noConflict = function() {
        $.fn.nerdAiFileUpload = old;
        return this;
    }

    $(document).render(function() {
        $('[data-control="nerdai-fileupload"]').nerdAiFileUpload();
    });

}(window.jQuery);
