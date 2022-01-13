// use the camelized version of HTML element id
Dropzone.options.uploadBrowse = {
    // The name that will be used to transfer the file
    paramName: "file",
    
    maxFilesize: 512, // MB

    chunking: true,
    forceChunking: true,
    chunkSize: 1000000,

    // Timeout after which the upload should be aborted. Default is 30000.
    // Given in microseconds. Does not trigger an error on timeout...
    timeout: 86400000,
    
    // Allows cancelling the upload
    addRemoveLinks: true,
    
    // Center message in the dropzone
    dictDefaultMessage: "<strong>Drop File here<br /><u>Choose File</u></strong>",
    
    dictCancelUpload: "Cancel Upload",
    
    // don't show it because it might be confusing while not being so useful
    dictRemoveFile: "",
    
    init: function() {
        this.on("success", function(file) {
            document.getElementById('upload_success_alert').style.display = 'block';
        });
    }
};
