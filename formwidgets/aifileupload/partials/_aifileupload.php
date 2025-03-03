<div
    data-control="nerdai-fileupload"
    class="nerdai-fileupload-container"
>
    <div
        id="<?= $this->getId('fileupload') ?>"
        class="nerdai-fileupload-dropzone"
        data-request="<?= $this->getEventHandler('onUpload') ?>"
        data-request-files
        data-request-flash
    >
        <input
            type="file"
            id="<?= $this->getId('input') ?>"
            name="file"
            accept="image/*"
            style="display: none;"
            data-browse-label="Select Image"
        />
        <div class="upload-ui">
            <i class="icon-upload"></i>
            <p>Drop an image here or click to upload</p>
            <small>Supported formats: JPG, PNG, GIF</small>
        </div>
    </div>

    <div class="nerdai-fileupload-preview hidden">
        <img src="" alt="Preview" id="<?= $this->getId('preview') ?>" />
        <div class="analysis-results">
            <h4>AI Analysis Results:</h4>
            <div id="<?= $this->getId('analysis') ?>" class="analysis-content"></div>
        </div>
    </div>

    <div class="nerdai-fileupload-error hidden">
        <p class="error-message"></p>
    </div>
</div>
